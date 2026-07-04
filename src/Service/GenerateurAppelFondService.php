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
        \DateTimeImmutable $dateEcheance,
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

        // =====================
        // Répartition
        // =====================

        foreach ($lots as $lot) {
            $tantiemeLot = $lot->getTantiemes();
            $montantLot
                = ($montantTotalBudget * $tantiemeLot) / $totalTantiemes;

            // =====================
            // Copropriétaires actifs
            // =====================

            foreach ($lot->getLotCoproprietaires() as $lotCoproprietaire) {

                $dateDebut = $lotCoproprietaire->getDateDebut();
                $dateFin = $lotCoproprietaire->getDateFin();

                if ($dateDebut > $dateAppel) {
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
                $ligne->setCoproprietaire($lotCoproprietaire->getCoproprietaire());
                $ligne->setPourcentage($pourcentage);
                $ligne->setMontant(round($montantCoproprietaire, 2));

                $appelFond->addLigneAppelFond($ligne);

                $this->entityManager->persist($ligne);
            }
        }

        // =====================
        // Sauvegarde
        // =====================

        $this->entityManager->persist($appelFond);
        $this->entityManager-> flush();

        return $appelFond;
    }
}
