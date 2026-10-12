<?php

namespace App\Service\Travaux;

use App\Entity\FactureFournisseur;
use App\Enum\FactureFournisseurType;
use App\Enum\OperationType;
use App\Enum\OperationStatut;
use App\Service\ComptabiliteService;

class GenerationRealisationChantierService
{
    public function __construct(
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function generer(
        FactureFournisseur $facture
    ): void {
        $chantier = $facture->getChantier();

        $ancienneRealisation =
            $facture->getOperationRealisationTravaux();

        if (
            $ancienneRealisation !== null
            && $ancienneRealisation->getStatut()
            !== OperationStatut::ANNULE
        ) {
            throw new \LogicException(
                'La réalisation comptable de cette facture de travaux a déjà été générée.'
            );
        }

        if ($chantier === null) {
            throw new \LogicException(
                'La facture n’est rattachée à aucun chantier.'
            );
        }

        $compteProvision = $chantier->getCompteProvision();
        $compteProduit = $chantier->getCompteProduit();

        if ($compteProvision === null) {
            throw new \LogicException(
                'Le compte de provision du chantier est introuvable.'
            );
        }

        if ($compteProduit === null) {
            throw new \LogicException(
                'Le compte de produit du chantier est introuvable.'
            );
        }

        $montant = (float) $facture->getMontant();

        if ($montant <= 0) {
            throw new \LogicException(
                'Le montant de la facture est invalide.'
            );
        }

        $operation = $this->comptabiliteService->creerOperation(
            $facture->getDateFacture(),
            'Réalisation travaux - ' . $chantier->getLibelle(),
            OperationType::REALISATION_TRAVAUX,
            $facture->getNumero()
        );

        if (
            $facture->getType()
            === FactureFournisseurType::AVOIR
        ) {
            // Annulation d'une partie des travaux réalisés :
            // 702xxx D / 102xxx C

            $this->comptabiliteService->creerDebit(
                $operation,
                $facture->getExercice(),
                $compteProduit,
                $facture->getMontant()
            );

            $this->comptabiliteService->creerCredit(
                $operation,
                $facture->getExercice(),
                $compteProvision,
                $facture->getMontant()
            );
        } else {
            // Réalisation des travaux :
            // 102xxx D / 702xxx C

            $this->comptabiliteService->creerDebit(
                $operation,
                $facture->getExercice(),
                $compteProvision,
                $facture->getMontant()
            );

            $this->comptabiliteService->creerCredit(
                $operation,
                $facture->getExercice(),
                $compteProduit,
                $facture->getMontant()
            );
        }

        $facture->setOperationRealisationTravaux($operation);

        $this->comptabiliteService->enregistrer();
    }
}
