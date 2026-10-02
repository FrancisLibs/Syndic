<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Travaux\Chantier;
use App\Entity\LigneAppelFond;
use App\Enum\ModeRepartition;
use App\Repository\AppelFondRepository;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurAppelChantierService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LotRepository $lotRepository,
        private AppelFondRepository $appelFondRepository,
    ) {}

    public function generer(
        Chantier $chantier,
        \DateTimeImmutable $dateAppel,
        \DateTimeImmutable $dateReglement,
    ): AppelFond {

        // =====================
        // Contrôles
        // =====================

        if ($chantier->isCloture()) {
            throw new \LogicException(
                'Impossible de générer un appel pour un chantier clôturé.'
            );
        }

        if (!$chantier->getAppels()->isEmpty()) {
            throw new \LogicException(
                'Un appel de fonds a déjà été généré pour ce chantier.'
            );
        }

        $montantTotal = (float) $chantier->getMontantVote();

        if ($montantTotal <= 0) {
            throw new \LogicException(
                'Le montant voté du chantier est invalide.'
            );
        }

        // =====================
        // Création de l'appel
        // =====================

        $appelFond = new AppelFond();

        $appelFond
            ->setChantier($chantier)
            ->setLibelle(
                'Appel travaux - ' . $chantier->getLibelle()
            )
            ->setDateAppel($dateAppel)
            ->setDateReglement($dateReglement)
            ->setMontantTotal(
                number_format($montantTotal, 2, '.', '')
            );

        // =====================
        // Numéro de l'appel
        // =====================

        $annee = (int) $dateAppel->format('Y');

        $prochainNumero =
            $this->appelFondRepository->countForYear($annee) + 1;

        $reference = sprintf(
            'AF-%d-%03d',
            $annee,
            $prochainNumero
        );

        $appelFond->setNumero($reference);

        // =====================
        // Lots
        // =====================

        $lots = $this->lotRepository->findBy([
            'copropriete' => $chantier->getCopropriete(),
        ]);

        if ($lots === []) {
            throw new \LogicException(
                'Aucun lot trouvé pour cette copropriété.'
            );
        }

        // =====================
        // Préparation répartition
        // =====================

        $lignesARepartir = [];

        $totalTantiemes = 0;

        if (
            $chantier->getModeRepartition()
            === ModeRepartition::TANTIEMES
        ) {
            foreach ($lots as $lot) {
                $totalTantiemes += $lot->getTantiemes();
            }

            if ($totalTantiemes <= 0) {
                throw new \LogicException(
                    'Le total des tantièmes est invalide.'
                );
            }
        }

        foreach ($lots as $lot) {

            foreach (
                $lot->getLotCoproprietaires()
                as $lotCoproprietaire
            ) {
                $dateDebut = $lotCoproprietaire->getDateDebut();
                $dateFin = $lotCoproprietaire->getDateFin();

                if ($dateDebut > $dateAppel) {
                    continue;
                }

                if ($dateFin && $dateFin < $dateAppel) {
                    continue;
                }

                $pourcentage =
                    (float) $lotCoproprietaire->getPourcentage();

                if (
                    $chantier->getModeRepartition()
                    === ModeRepartition::TANTIEMES
                ) {
                    $montantLot =
                        $montantTotal
                        * $lot->getTantiemes()
                        / $totalTantiemes;
                } else {
                    $montantLot =
                        $montantTotal / count($lots);
                }

                $montantBrut =
                    $montantLot * $pourcentage / 100;

                $lignesARepartir[] = [
                    'lot' => $lot,
                    'coproprietaire' =>
                    $lotCoproprietaire->getCoproprietaire(),
                    'pourcentage' => $pourcentage,
                    'montantBrut' => $montantBrut,
                ];
            }
        }

        if ($lignesARepartir === []) {
            throw new \LogicException(
                'Aucun copropriétaire actif trouvé à la date de l’appel.'
            );
        }

        // =====================
        // Création des lignes
        // =====================

        $totalGenere = 0.0;
        $dernierIndex = count($lignesARepartir) - 1;

        foreach ($lignesARepartir as $index => $donnees) {

            if ($index === $dernierIndex) {
                $montant = round(
                    $montantTotal - $totalGenere,
                    2
                );
            } else {
                $montant = round(
                    $donnees['montantBrut'],
                    2
                );

                $totalGenere += $montant;
            }

            $ligne = new LigneAppelFond();

            $ligne
                ->setMontantRegle('0.00')
                ->setSoldee(false)
                ->setAppelFond($appelFond)
                ->setLot($donnees['lot'])
                ->setCoproprietaire(
                    $donnees['coproprietaire']
                )
                ->setPourcentage(
                    $donnees['pourcentage']
                )
                ->setMontant(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

            $appelFond->addLigneAppelFond($ligne);

            $this->entityManager->persist($ligne);
        }

        // =====================
        // Sauvegarde
        // =====================

        $this->entityManager->persist($appelFond);
        $this->entityManager->flush();

        return $appelFond;
    }
}
