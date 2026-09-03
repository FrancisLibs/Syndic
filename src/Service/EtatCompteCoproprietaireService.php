<?php

namespace App\Service;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Enum\OperationStatut;
use App\Repository\EcritureRepository;

class EtatCompteCoproprietaireService
{
    public function __construct(
        private readonly EcritureRepository $ecritureRepository,
    ) {}

    public function construire(
        Coproprietaire $coproprietaire,
        Exercice $exercice
    ): ?array {
        $compte = $coproprietaire->getCompte();

        if (!$compte) {
            return null;
        }

        $ecritures = $this->ecritureRepository->findBy(
            [
                'compte' => $compte,
                'exercice' => $exercice,
            ],
            [
                'date' => 'ASC',
                'id' => 'ASC',
            ]
        );

        $mouvements = [];
        $solde = 0.0;

        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            if (
                !$operation
                || $operation->getStatut()
                !== OperationStatut::VALIDE
            ) {
                continue;
            }

            $debit = (float) $ecriture->getDebit();
            $credit = (float) $ecriture->getCredit();

            $solde = round(
                $solde + $debit - $credit,
                2
            );

            $mouvements[] = [
                'exercice' => $ecriture->getExercice()->getNom(),
                'date' => $ecriture->getDate(),
                'piece' => $operation->getPiece(),
                'libelle' => $operation->getLibelle(),
                'type' => $operation->getType()?->value,
                'debit' => $debit,
                'credit' => $credit,
                'solde' => $solde,
            ];
        }

        return [
            'coproprietaire' => $coproprietaire,
            'compte' => $compte,
            'mouvements' => $mouvements,
            'totalDebit' => array_sum(
                array_column($mouvements, 'debit')
            ),
            'totalCredit' => array_sum(
                array_column($mouvements, 'credit')
            ),
            'solde' => $solde,
        ];
    }
}
