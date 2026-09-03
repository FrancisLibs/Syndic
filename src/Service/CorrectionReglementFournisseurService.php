<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\ReglementFournisseur;
use App\Enum\OperationType;
use Doctrine\ORM\EntityManagerInterface;

final class CorrectionReglementFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function corrigerMontant(
        ReglementFournisseur $reglement,
        string $nouveauMontant
    ): void {
        $nouveauMontant =
            $this->normaliserMontant(
                $nouveauMontant
            );

        if ((float) $nouveauMontant <= 0) {
            throw new \LogicException(
                'Le montant du règlement doit être supérieur à zéro.'
            );
        }

        $exercice = $reglement->getExercice();

        if ($exercice === null) {
            throw new \LogicException(
                'L’exercice du règlement est introuvable.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible de corriger un règlement d’un exercice clôturé.'
            );
        }

        $operation = $reglement->getOperation();

        if ($operation === null) {
            throw new \LogicException(
                'L’opération comptable du règlement est introuvable.'
            );
        }

        if (
            $operation->getType()
            !== OperationType::PAIEMENT_FOURNISSEUR
        ) {
            throw new \LogicException(
                'L’opération associée n’est pas un règlement fournisseur.'
            );
        }

        $ecritureDebit = null;
        $ecritureCredit = null;

        foreach ($operation->getEcritures() as $ecriture) {

            if ((float) $ecriture->getDebit() > 0) {

                if ($ecritureDebit !== null) {
                    throw new \LogicException(
                        'Plusieurs écritures de débit ont été trouvées.'
                    );
                }

                $ecritureDebit = $ecriture;
            }

            if ((float) $ecriture->getCredit() > 0) {

                if ($ecritureCredit !== null) {
                    throw new \LogicException(
                        'Plusieurs écritures de crédit ont été trouvées.'
                    );
                }

                $ecritureCredit = $ecriture;
            }
        }

        if (
            !$ecritureDebit instanceof Ecriture
            || !$ecritureCredit instanceof Ecriture
        ) {
            throw new \LogicException(
                'Les écritures comptables du règlement sont incomplètes.'
            );
        }

        $this->entityManager->wrapInTransaction(
            function () use (
                $reglement,
                $nouveauMontant,
                $ecritureDebit,
                $ecritureCredit
            ): void {

                $reglement->setMontant(
                    $nouveauMontant
                );

                $ecritureDebit->setDebit(
                    $nouveauMontant
                );

                $ecritureCredit->setCredit(
                    $nouveauMontant
                );

                $operation =
                    $reglement->getOperation();

                if (
                    $operation === null
                    || !$operation->isEquilibree()
                ) {
                    throw new \LogicException(
                        'L’opération comptable du règlement n’est plus équilibrée.'
                    );
                }

                $this->entityManager->flush();
            }
        );
    }

    private function normaliserMontant(
        string $montant
    ): string {
        $montant = str_replace(
            [' ', ','],
            ['', '.'],
            trim($montant)
        );

        if (
            $montant === ''
            || !is_numeric($montant)
        ) {
            throw new \LogicException(
                'Le montant du règlement est invalide.'
            );
        }

        return number_format(
            (float) $montant,
            2,
            '.',
            ''
        );
    }
}
