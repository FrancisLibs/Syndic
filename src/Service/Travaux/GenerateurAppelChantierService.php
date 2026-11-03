<?php

namespace App\Service\Travaux;

use App\Entity\Travaux\VoteComplementaireChantier;
use App\Entity\AppelFond;
use App\Entity\LigneAppelFond;
use App\Entity\Travaux\Chantier;
use App\Enum\ModeRepartition;
use App\Enum\AssembleeGeneraleStatut;
use App\Repository\ResolutionRepository;
use App\Repository\AppelFondRepository;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurAppelChantierService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LotRepository $lotRepository,
        private AppelFondRepository $appelFondRepository,
        private GenerationAppelChantierComptableService $generationComptable,
        private readonly ResolutionRepository $resolutionRepository,
    ) {}

    public function generer(
        Chantier $chantier,
        \DateTimeImmutable $dateAppel,
    ): AppelFond {

        $this->verifierChantierPourAppel($chantier);

        $this->verifierResolutionAdoptee([
            'chantier' => $chantier,
        ]);

        if ($chantier->getAppelInitial() !== null) {
            throw new \LogicException(
                'L’appel initial de ce chantier a déjà été généré.'
            );
        }

        $montant = (float) $chantier->getMontantVote();

        if ($montant <= 0) {
            throw new \LogicException(
                'Le montant voté du chantier est invalide.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function () use ($chantier, $dateAppel): AppelFond {

                $appelFond = $this->genererAppel(
                    $chantier,
                    $dateAppel,
                    (float) $chantier->getMontantVote(),
                    'Appel travaux - ' . $chantier->getLibelle()
                );

                $chantier->setAppelInitial($appelFond);

                return $appelFond;
            }
        );
    }

    public function genererComplementaire(
        VoteComplementaireChantier $vote,
        \DateTimeImmutable $dateAppel,
    ): AppelFond {

        $chantier = $vote->getChantier();

        if (!$chantier) {
            throw new \LogicException(
                'Le vote complémentaire n’est rattaché à aucun chantier.'
            );
        }

        $this->verifierChantierPourAppel($chantier);

        $this->verifierResolutionAdoptee([
            'voteComplementaireChantier' => $vote,
        ]);

        if ($chantier->getAppelInitial() === null) {
            throw new \LogicException(
                'L’appel initial du chantier doit être généré avant un appel complémentaire.'
            );
        }

        if ($vote->getAppelFond() !== null) {
            throw new \LogicException(
                'Ce vote complémentaire a déjà fait l’objet d’un appel de fonds.'
            );
        }

        if ($dateAppel < $vote->getDateVote()) {
            throw new \LogicException(
                'La date de l’appel ne peut pas être antérieure à la date du vote complémentaire.'
            );
        }

        $montant = (float) $vote->getMontant();

        if ($montant <= 0) {
            throw new \LogicException(
                'Le montant du vote complémentaire est invalide.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function () use (
                $chantier,
                $vote,
                $dateAppel,
                $montant
            ): AppelFond {

                $appelFond = $this->genererAppel(
                    $chantier,
                    $dateAppel,
                    $montant,
                    sprintf(
                        'Appel complémentaire travaux - %s - Résolution %s',
                        $chantier->getLibelle(),
                        $vote->getNumeroResolution()
                    )
                );

                $vote->setAppelFond($appelFond);

                return $appelFond;
            }
        );
    }

    private function genererAppel(
        Chantier $chantier,
        \DateTimeImmutable $dateAppel,
        float $montantTotal,
        string $libelle,
    ): AppelFond {

        $this->verifierChantierPourAppel($chantier);

        // =====================
        // Création de l'appel
        // =====================

        $appelFond = new AppelFond();

        $appelFond
            ->setChantier($chantier)
            ->setLibelle($libelle)
            ->setDateAppel($dateAppel)
            ->setMontantTotal(
                number_format($montantTotal, 2, '.', '')
            );

        // =====================
        // Numéro de l'appel
        // =====================

        $annee = (int) $dateAppel->format('Y');

        $reference = $this->appelFondRepository
            ->getProchainNumero($annee);

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

        // Comptabilisation de l'appel travaux
        $this->generationComptable->generer($appelFond);

        return $appelFond;
    }

    private function verifierChantierPourAppel(
        Chantier $chantier
    ): void {

        if (!$chantier->estFinanceParAppels()) {
            throw new \LogicException(
                'Ce chantier est financé par les charges courantes. '
                    . 'Aucun appel de fonds spécifique ne peut être généré.'
            );
        }

        if ($chantier->isCloture()) {
            throw new \LogicException(
                'Impossible de générer un appel pour un chantier clôturé.'
            );
        }
    }

    private function verifierResolutionAdoptee(array $criteres): void
    {
        $resolutions = $this->resolutionRepository->findBy(
            $criteres + ['adoptee' => true]
        );

        foreach ($resolutions as $resolution) {
            $ag = $resolution->getAssembleeGenerale();

            if (
                $ag !== null
                && $ag->getStatut() === AssembleeGeneraleStatut::CLOTUREE
            ) {
                return;
            }
        }

        throw new \LogicException(
            'La génération de cet appel nécessite une résolution '
                . 'adoptée lors d’une assemblée générale clôturée.'
        );
    }
}
