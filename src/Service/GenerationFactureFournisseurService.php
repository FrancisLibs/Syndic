<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\OperationType;

class GenerationFactureFournisseurService
{
    public function __construct(
        private GenerationRepartitionService $generationRepartitionService,
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function generer(
        FactureFournisseur $facture
    ): void {
        $typeCharge = $facture->getTypeCharge();

        if ($typeCharge === null) {
            throw new \LogicException(
                'Aucun type de charge n’est défini.'
            );
        }

        $compteCharge = $typeCharge->getCompte();

        if (!$compteCharge) {
            throw new \LogicException(
                'Compte de charge introuvable.'
            );
        }

        $fournisseur = $facture->getFournisseur();

        if ($fournisseur === null) {
            throw new \LogicException(
                'Aucun fournisseur n’est défini.'
            );
        }

        $compteFournisseur = $fournisseur->getCompte();

        if (!$compteFournisseur) {
            throw new \LogicException(
                'Compte fournisseur introuvable.'
            );
        }

        $coproprietaireAvanceur =
            $facture->getCoproprietaireAvanceur();

        $compteCoproprietaire = null;

        if ($coproprietaireAvanceur !== null) {
            $compteCoproprietaire =
                $coproprietaireAvanceur->getCompte();

            if (!$compteCoproprietaire) {
                throw new \LogicException(
                    'Compte copropriétaire introuvable.'
                );
            }
        }

        /*
         * 1. Comptabilisation de la facture fournisseur
         *
         * Débit  : compte de charge
         * Crédit : compte fournisseur
         */
        $operationFacture = $this
            ->comptabiliteService
            ->creerOperation(
                $facture->getDateFacture(),
                $facture->getLibelle(),
                OperationType::CHARGE,
                $facture->getNumero()
            );

        $operationFacture
            ->setTypeCharge($typeCharge)
            ->setFournisseur($fournisseur);

        $ecritureCharge = $this
            ->comptabiliteService
            ->creerDebit(
                $operationFacture,
                $facture->getExercice(),
                $compteCharge,
                $facture->getMontant()
            );

        $this
            ->comptabiliteService
            ->creerCredit(
                $operationFacture,
                $facture->getExercice(),
                $compteFournisseur,
                $facture->getMontant()
            );

        $this->comptabiliteService->enregistrer();

        /*
         * 2. Transfert éventuel de la dette vers le copropriétaire
         *
         * Débit  : compte fournisseur
         * Crédit : compte copropriétaire
         */
        if (
            $coproprietaireAvanceur !== null
            && $compteCoproprietaire !== null
        ) {
            $operationTransfert = $this
                ->comptabiliteService
                ->creerOperation(
                    $facture->getDateFacture(),
                    'Transfert de la dette '
                        . $fournisseur->getNom()
                        . ' vers '
                        . (string) $coproprietaireAvanceur,
                    OperationType::TRANSFERT_DETTE,
                    $facture->getNumero()
                );

            $operationTransfert
                ->setFournisseur($fournisseur);

            $this
                ->comptabiliteService
                ->creerDebit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteFournisseur,
                    $facture->getMontant()
                );

            $this
                ->comptabiliteService
                ->creerCredit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteCoproprietaire,
                    $facture->getMontant(),
                    $coproprietaireAvanceur
                );

            $this->comptabiliteService->enregistrer();
        }

        $facture
            ->setOperation($operationFacture)
            ->setComptabilisee(true);

        /*
         * La répartition repose uniquement sur l’écriture
         * de charge de la facture.
         */
        if (!$typeCharge->isEau()) {
            $this
                ->generationRepartitionService
                ->generer(
                    $ecritureCharge,
                    $facture
                );
        }
    }
}
