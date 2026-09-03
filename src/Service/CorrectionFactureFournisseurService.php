<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use Doctrine\ORM\EntityManagerInterface;

final class CorrectionFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GenerationRepartitionService $generationRepartitionService,
    ) {}

    public function corrigerMontant(
        FactureFournisseur $facture,
        string $nouveauMontant
    ): void {
        $nouveauMontant =
            $this->normaliserMontant(
                $nouveauMontant
            );

        if ((float) $nouveauMontant <= 0) {
            throw new \LogicException(
                'Le nouveau montant doit être supérieur à zéro.'
            );
        }

        if ($facture->getExercice()?->isCloture()) {
            throw new \LogicException(
                'Impossible de corriger une facture d’un exercice clôturé.'
            );
        }

        if ($facture->getCoproprietaireAvanceur() !== null) {
            throw new \LogicException(
                'La correction d’une facture avancée par un copropriétaire n’est pas encore prise en charge.'
            );
        }

        if (!$facture->isComptabilisee()) {
            throw new \LogicException(
                'Cette facture n’est pas comptabilisée.'
            );
        }

        $operation = $facture->getOperation();

        if ($operation === null) {
            throw new \LogicException(
                'L’opération comptable de la facture est introuvable.'
            );
        }

        if ($operation->getType() !== OperationType::CHARGE) {
            throw new \LogicException(
                'L’opération associée à la facture n’est pas une opération de charge.'
            );
        }

        $ecritureCharge = null;
        $ecritureFournisseur = null;

        foreach ($operation->getEcritures() as $ecriture) {

            if ((float) $ecriture->getDebit() > 0) {
                if ($ecritureCharge !== null) {
                    throw new \LogicException(
                        'Plusieurs écritures de débit ont été trouvées.'
                    );
                }

                $ecritureCharge = $ecriture;
            }

            if ((float) $ecriture->getCredit() > 0) {
                if ($ecritureFournisseur !== null) {
                    throw new \LogicException(
                        'Plusieurs écritures de crédit ont été trouvées.'
                    );
                }

                $ecritureFournisseur = $ecriture;
            }
        }

        if (
            !$ecritureCharge instanceof Ecriture
            || !$ecritureFournisseur instanceof Ecriture
        ) {
            throw new \LogicException(
                'Les écritures comptables de la facture sont incomplètes.'
            );
        }

        $this->entityManager->wrapInTransaction(
            function () use (
                $facture,
                $nouveauMontant,
                $ecritureCharge,
                $ecritureFournisseur
            ): void {

                foreach (
                    $ecritureCharge->getRepartitions()->toArray()
                    as $repartition
                ) {
                    $this->entityManager->remove(
                        $repartition
                    );
                }

                $facture->setMontant(
                    $nouveauMontant
                );

                $ecritureCharge->setDebit(
                    $nouveauMontant
                );

                $ecritureFournisseur->setCredit(
                    $nouveauMontant
                );

                $typeCharge = $facture->getTypeCharge();

                if (
                    $typeCharge !== null
                    && !$typeCharge->isEau()
                ) {
                    $this->generationRepartitionService
                        ->generer(
                            $ecritureCharge,
                            $facture
                        );
                }

                if (
                    !$facture->getOperation()
                        ?->isEquilibree()
                ) {
                    throw new \LogicException(
                        'L’opération comptable n’est plus équilibrée après correction.'
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
                'Le montant est invalide.'
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
