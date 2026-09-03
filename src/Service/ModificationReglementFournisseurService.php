<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\ReglementFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ModificationReglementFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompteRepository $compteRepository,
    ) {}

    public function modifier(
        ReglementFournisseur $reglement
    ): void {
        $exercice = $reglement->getExercice();

        if ($exercice === null) {
            throw new \LogicException(
                'L’exercice du règlement est introuvable.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible de modifier un règlement d’un exercice clôturé.'
            );
        }

        $dateReglement =
            $reglement->getDateReglement();

        if ($dateReglement === null) {
            throw new \LogicException(
                'La date du règlement est obligatoire.'
            );
        }

        if (
            $dateReglement < $exercice->getDateDebut()
            || $dateReglement > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date du règlement ne correspond pas à la période de l’exercice.'
            );
        }

        $montant = $this->normaliserMontant(
            $reglement->getMontant()
        );

        if ((float) $montant <= 0) {
            throw new \LogicException(
                'Le montant du règlement doit être supérieur à zéro.'
            );
        }

        $fournisseur =
            $reglement->getFournisseur();

        if ($fournisseur === null) {
            throw new \LogicException(
                'Le fournisseur est obligatoire.'
            );
        }

        $compteFournisseur =
            $fournisseur->getCompte();

        if ($compteFournisseur === null) {
            throw new \LogicException(
                'Le fournisseur ne possède aucun compte comptable.'
            );
        }

        $operation =
            $reglement->getOperation();

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

        $compteBanque =
            $this->compteRepository
            ->findByNumeroOrFail('512000');

        $ecritureDebit = null;
        $ecritureCredit = null;

        foreach ($operation->getEcritures() as $ecriture) {

            if ((float) $ecriture->getDebit() > 0) {
                $ecritureDebit = $ecriture;
            }

            if ((float) $ecriture->getCredit() > 0) {
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

        $reference = trim(
            (string) $reglement->getReference()
        );

        $libelle =
            'Règlement '
            . $fournisseur->getNom();

        if ($reference !== '') {
            $libelle .=
                ' - '
                . $reference;
        }

        $this->entityManager->wrapInTransaction(
            function () use (
                $reglement,
                $operation,
                $dateReglement,
                $montant,
                $fournisseur,
                $compteFournisseur,
                $compteBanque,
                $ecritureDebit,
                $ecritureCredit,
                $reference,
                $libelle
            ): void {

                $reglement->setMontant(
                    $montant
                );

                $operation
                    ->setDate(
                        $dateReglement
                    )
                    ->setLibelle(
                        $libelle
                    )
                    ->setPiece(
                        $reference !== ''
                            ? $reference
                            : null
                    )
                    ->setFournisseur(
                        $fournisseur
                    );

                $ecritureDebit
                    ->setCompte(
                        $compteFournisseur
                    )
                    ->setDate(
                        $dateReglement
                    )
                    ->setDebit(
                        $montant
                    );

                $ecritureCredit
                    ->setCompte(
                        $compteBanque
                    )
                    ->setDate(
                        $dateReglement
                    )
                    ->setCredit(
                        $montant
                    );

                if (!$operation->isEquilibree()) {
                    throw new \LogicException(
                        'L’opération comptable n’est plus équilibrée.'
                    );
                }

                $this->entityManager->flush();
            }
        );
    }

    private function normaliserMontant(
        float|string|null $montant
    ): string {
        if ($montant === null) {
            throw new \LogicException(
                'Le montant du règlement est obligatoire.'
            );
        }

        if (is_string($montant)) {
            $montant = str_replace(
                [' ', ','],
                ['', '.'],
                trim($montant)
            );
        }

        if (!is_numeric($montant)) {
            throw new \LogicException(
                'Montant de règlement invalide.'
            );
        }

        return number_format(
            round(
                (float) $montant,
                2
            ),
            2,
            '.',
            ''
        );
    }
}
