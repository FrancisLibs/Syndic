<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Budget;
use App\Entity\LigneAppelFond;
use App\Entity\Lot;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurAppelFondService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LotRepository $lotRepository,
    ) {}

    public function generer(
        Budget $budget,
        \DateTimeImmutable $dateAppel,
        \DateTimeImmutable $dateEcheance
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

        $appelFond->setDateEcheance(
            $dateEcheance
        );

        $appelFond->setMontantTotal($montantTotalBudget);

        $budget->setVerrouille(true);

        // =====================
        // Lots copropriété
        // =====================

        $lots =
            $this->lotRepository->findBy(
                [
                    'copropriete' =>
                    $budget->getCopropriete()
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

        // =====================
        // Répartition
        // =====================

        foreach ($lots as $lot) {

            $montantLot
                = (
                    $montantTotalBudget
                    * $lot->getTantiemes()
                )
                / $totalTantiemes;

            // =====================
            // Copropriétaires actifs
            // =====================

            foreach (
                $lot->getLotCoproprietaires()
                as $lotCoproprietaire
            ) {

                $dateFin = $lotCoproprietaire
                    ->getDateFin();

                if ($lotCoproprietaire->getDateDebut() > $dateAppel) {
                    continue;
                }

                if ($dateFin && $dateFin < $dateAppel) {
                    continue;
                }

                $pourcentage = (float) $lotCoproprietaire->getPourcentage();

                $montantCoproprietaire = $montantLot * $pourcentage / 100;

                $ligne = new LigneAppelFond();

                $ligne->setMontantRegle('0.00');
                $ligne->setSoldee(false);

                $ligne->setAppelFond($appelFond);

                $ligne->setLot($lot);

                $ligne->setCoproprietaire(
                    $lotCoproprietaire
                        ->getCoproprietaire()
                );

                $ligne->setPourcentage(
                    $pourcentage
                );

                $ligne->setMontant(round($montantCoproprietaire, 2));

                $appelFond->addLigneAppelFond($ligne);

                $this->entityManager->persist($ligne);
            }
        }

        // =====================
        // Sauvegarde
        // =====================

        $this->entityManager
            ->persist($appelFond);

        $this->entityManager
            ->flush();

        return $appelFond;
    }
}
