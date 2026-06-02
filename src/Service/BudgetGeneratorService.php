<?php

namespace App\Service;

use App\Entity\Budget;
use App\Entity\Compte;
use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\OperationRepository;

class BudgetGeneratorService
{
    public function __construct(
        private OperationRepository $operationRepo
    ) {}

    public function generer(
        Budget $budget,
        array $lots,
        Compte $compteCharge,
        Compte $compteCopro,
        RepartitionService $repartitionService,
    ): array {

        $operations = [];

        $nbAppels = $budget->getNbAppels();
        $montantTotal = (float) $budget->getMontantTotal();

        if ($nbAppels <= 0) {
            throw new \LogicException('Nombre d\'appels invalide');
        }

        if ($montantTotal <= 0) {
            throw new \LogicException('Montant total invalide');
        }

        if (empty($lots)) {
            throw new \LogicException('Aucun lot pour générer les appels');
        }

        $montantAppel = $montantTotal / $nbAppels;

        $date = \DateTimeImmutable::createFromInterface(
            $budget->getDateDebut()
        );

        $exercice = $budget->getExercice();

        if (!$exercice) {
            throw new \LogicException('Budget sans exercice');
        }

        $totalGenere = 0;

        for ($i = 0; $i < $nbAppels; $i++) {

            // 🔒 Anti-doublon par date
            if ($this->operationRepo->existsAppelFondsForDate($date, $exercice)) {
                throw new \LogicException(
                    'Un appel de fonds existe déjà pour la date ' . $date->format('Y-m-d')
                );
            }

            // 💰 Gestion des arrondis (dernier ajusté)
            if ($i === $nbAppels - 1) {
                $montant = $montantTotal - $totalGenere;
            } else {
                $montant = round($montantAppel, 2);
                $totalGenere += $montant;
            }

            $montantStr = number_format($montant, 2, '.', '');

            // 🧾 Création opération
            $operation = new Operation();
            $operation->setDate($date);
            $operation->setType(OperationType::APPEL_FONDS);
            $operation->setLibelle(
                ($budget->getLibelle() ?? 'Appel de fonds') . ' #' . ($i + 1)
            );

            // 💸 Écriture charge
            $ecritureCharge = new Ecriture();
            $ecritureCharge->setOperation($operation)
                ->setExercice($exercice)
                ->setCompte($compteCharge)
                ->setDebit($montantStr)
                ->setCredit('0.00');

            // 📊 Répartition (tantièmes ou autre selon ton service)
            $repartitionService->generer($ecritureCharge, $lots);

            // 💰 Écriture copropriétaire
            $ecritureCopro = new Ecriture();
            $ecritureCopro->setOperation($operation)
                ->setExercice($exercice)
                ->setCompte($compteCopro)
                ->setDebit('0.00')
                ->setCredit($montantStr);

            // ✔ Validation comptable
            $ecritureCharge->validate();
            $ecritureCopro->validate();

            $operations[] = $operation;

            // ⏱ Incrément date
            $date = match ($budget->getFrequence()) {
                'mensuel' => $date->modify('+1 month'),
                'trimestriel' => $date->modify('+3 months'),
                default => throw new \LogicException('Fréquence inconnue')
            };
        }

        return $operations;
    }
}
