<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Budget;
use App\Entity\LigneAppelFond;
use App\Repository\AppelFondRepository;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurAppelFondService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LotRepository $lotRepository,
        private AppelFondRepository $appelFondRepository,
    ) {}

    public function generer(
        Budget $budget,
        \DateTimeImmutable $dateAppel,
        \DateTimeImmutable $dateReglement,
    ): AppelFond {

        // =====================
        // Total budget
        // =====================

        $montantTotalBudget = 0;

        foreach ($budget->getLignes() as $ligne) {

            $montantTotalBudget +=
                (float) $ligne->getMontant();
        }

        // =====================
        // Création appel
        // =====================

        $appelFond = new AppelFond();
        $appelFond->setBudget($budget);
        $appelFond->setLibelle('Appel de fonds - ' . $budget->getLibelle());
        $appelFond->setDateAppel($dateAppel);
        $appelFond->setDateReglement(
            $dateReglement
        );

        $appelFond->setMontantTotal($montantTotalBudget);

        // 🌟 Automatisation du numéro (Ex: AF-2026-001)
        $anneeEnCours = (int) $dateAppel->format('Y');

        // 🌟 On appelle la méthode du Repository qu'on vient de créer
        $totalAppelsCetteAnnee = $this->appelFondRepository->countForYear($anneeEnCours);

        $prochainNumero = $totalAppelsCetteAnnee + 1;

        // Génère la chaîne (ex: AF-2026-001)
        $reference = "AF-" . $anneeEnCours . "-" . str_pad($prochainNumero, 3, "0", STR_PAD_LEFT);

        $appelFond->setNumero($reference);

        $budget->setVerrouille(true);

        // =====================
        // Lots copropriété
        // =====================

        $copropriete = $budget->getCopropriete();

        $lots =
            $this->lotRepository->findBy(
                [
                    'copropriete' => $copropriete
                ]
            );

        // =====================
        // Total tantièmes
        // =====================

        $totalTantiemes = 0;

        foreach ($lots as $lot) {

            $totalTantiemes +=
                $lot->getTantiemes();
        }

        if ($totalTantiemes <= 0) {
            throw new \LogicException(
                'Le total des tantièmes est invalide.'
            );
        }

        // =====================
        // Répartition
        // =====================

        $lignesARepartir = [];

        foreach ($lots as $lot) {
            $tantiemeLot = $lot->getTantiemes();

            $montantLot =
                ($montantTotalBudget * $tantiemeLot)
                / $totalTantiemes;

            foreach ($lot->getLotCoproprietaires() as $lotCoproprietaire) {
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

        $totalGenere = 0.0;
        $dernierIndex = count($lignesARepartir) - 1;

        foreach ($lignesARepartir as $index => $donnees) {
            if ($index === $dernierIndex) {
                $montant = round(
                    $montantTotalBudget - $totalGenere,
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
