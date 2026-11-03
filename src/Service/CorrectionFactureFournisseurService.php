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

        [
            $ecritureDebit,
            $ecritureCredit,
        ] = $this->trouverEcrituresDebitCredit(
            $operation
        );

        if ($facture->estFacture()) {
            $ecritureCharge = $ecritureDebit;
            $ecritureFournisseur = $ecritureCredit;
        } else {
            $ecritureCharge = $ecritureCredit;
            $ecritureFournisseur = $ecritureDebit;
        }

        $ecritureRealisationDebit = null;
        $ecritureRealisationCredit = null;

        if ($facture->getChantier() !== null) {

            $operationRealisation =
                $facture->getOperationRealisationTravaux();

            if ($operationRealisation === null) {
                throw new \LogicException(
                    'L’opération de réalisation des travaux est introuvable.'
                );
            }

            if (
                $operationRealisation->getType()
                !== OperationType::REALISATION_TRAVAUX
            ) {
                throw new \LogicException(
                    'L’opération associée n’est pas une réalisation de travaux.'
                );
            }

            [
                $ecritureRealisationDebit,
                $ecritureRealisationCredit,
            ] = $this->trouverEcrituresDebitCredit(
                $operationRealisation
            );
        }

        $this->entityManager->wrapInTransaction(
            function () use (
                $facture,
                $nouveauMontant,
                $ecritureCharge,
                $ecritureFournisseur,
                $ecritureRealisationDebit,
                $ecritureRealisationCredit
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

                if ($facture->estFacture()) {

                    $ecritureCharge->setDebit(
                        $nouveauMontant
                    );

                    $ecritureFournisseur->setCredit(
                        $nouveauMontant
                    );
                } else {

                    $ecritureCharge->setCredit(
                        $nouveauMontant
                    );

                    $ecritureFournisseur->setDebit(
                        $nouveauMontant
                    );
                }

                // Réalisation comptable du chantier

                if (
                    $ecritureRealisationDebit !== null
                    && $ecritureRealisationCredit !== null
                ) {
                    $ecritureRealisationDebit->setDebit(
                        $nouveauMontant
                    );

                    $ecritureRealisationCredit->setCredit(
                        $nouveauMontant
                    );
                }

                $typeCharge = $facture->getTypeCharge();

                if (
                    $typeCharge !== null
                    && !$typeCharge->isEau()
                ) {
                    $montantARepartir =
                        (float) $nouveauMontant;

                    if ($facture->estAvoir()) {
                        $montantARepartir *= -1;
                    }

                    $this->generationRepartitionService
                        ->generer(
                            $ecritureCharge,
                            $facture->getExercice(),
                            $facture->getDateFacture(),
                            $montantARepartir
                        );
                }

                // Vérification de l'opération fournisseur

                if (
                    !$facture->getOperation()
                        ?->isEquilibree()
                ) {
                    throw new \LogicException(
                        'L’opération comptable n’est plus équilibrée après correction.'
                    );
                }

                // Vérification de la réalisation du chantier

                if (
                    $facture->getChantier() !== null
                    && !$facture->getOperationRealisationTravaux()
                        ?->isEquilibree()
                ) {
                    throw new \LogicException(
                        'L’opération de réalisation des travaux n’est plus équilibrée après correction.'
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

    private function trouverEcrituresDebitCredit(
        \App\Entity\Operation $operation
    ): array {
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
                'Les écritures comptables de l’opération sont incomplètes.'
            );
        }

        return [
            $ecritureDebit,
            $ecritureCredit,
        ];
    }
}
