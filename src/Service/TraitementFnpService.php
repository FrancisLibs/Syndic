<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;

class TraitementFnpService
{
    public function __construct(
        private ComptabiliteService $comptabiliteService,
        private CompteRepository $compteRepository,
        private EcritureRepository $ecritureRepository,
    ) {}

    public function traiter(
        FactureFournisseur $facture,
        Exercice $exercice,
        \DateTimeImmutable $dateFacture,
        string $numero,
        bool $imputerAcompte = false,
    ): void {
        if (!$facture->isFnp()) {
            throw new \LogicException(
                'Cette facture n’est pas une FNP.'
            );
        }

        if ($facture->isFnpTraitee()) {
            throw new \LogicException(
                'Cette FNP a déjà été traitée.'
            );
        }

        if (
            $dateFacture < $exercice->getDateDebut()
            || $dateFacture > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date de facture n’appartient pas à l’exercice courant.'
            );
        }

        $fournisseur = $facture->getFournisseur();

        if ($fournisseur === null) {
            throw new \LogicException(
                'Aucun fournisseur n’est associé à cette FNP.'
            );
        }

        $compteFournisseur = $fournisseur->getCompte();

        if ($compteFournisseur === null) {
            throw new \LogicException(
                'Compte fournisseur introuvable.'
            );
        }

        $compteFnp =
            $this->compteRepository
            ->findByNumeroOrFail('408000');

        $montant =
            (float) $facture->getMontant();

        /**
         * Vérification préalable de l'acompte.
         *
         * On ne crée aucune écriture tant que
         * cette vérification n'est pas terminée.
         */
        $compteAcompte = null;

        if ($imputerAcompte) {
            $compteAcompte =
                $this->compteRepository
                ->findByNumeroOrFail('409100');

            $soldeAcompte =
                $this->ecritureRepository
                ->calculerSoldeCompte(
                    $exercice,
                    $compteAcompte
                );

            if ($soldeAcompte < $montant) {
                throw new \LogicException(
                    'Le solde disponible en avances fournisseurs est insuffisant. '
                        . 'Disponible : '
                        . number_format(
                            $soldeAcompte,
                            2,
                            ',',
                            ' '
                        )
                        . ' €, nécessaire : '
                        . number_format(
                            $montant,
                            2,
                            ',',
                            ' '
                        )
                        . ' €.'
                );
            }
        }

        /**
         * Création de l'opération de traitement de la FNP.
         */
        $operation =
            $this->comptabiliteService
            ->creerOperation(
                $dateFacture,
                'Traitement FNP - ' . $facture->getLibelle(),
                OperationType::TRAITEMENT_FNP,
                $numero
            );

        $operation
            ->setFournisseur($fournisseur)
            ->setTypeCharge($facture->getTypeCharge());

        /**
         * 1. Extinction de la FNP :
         *
         * Débit  408000
         * Crédit fournisseur
         */
        $this->comptabiliteService->creerDebit(
            $operation,
            $exercice,
            $compteFnp,
            $montant
        );

        $this->comptabiliteService->creerCredit(
            $operation,
            $exercice,
            $compteFournisseur,
            $montant
        );

        /**
         * 2. Imputation éventuelle d'un acompte déjà versé :
         *
         * Débit  fournisseur
         * Crédit 409100
         */
        if (
            $imputerAcompte
            && $compteAcompte !== null
        ) {
            $this->comptabiliteService->creerDebit(
                $operation,
                $exercice,
                $compteFournisseur,
                $montant
            );

            $this->comptabiliteService->creerCredit(
                $operation,
                $exercice,
                $compteAcompte,
                $montant
            );
        }

        /**
         * La ligne FactureFournisseur devient
         * la facture réellement reçue.
         */
        $facture
            ->setDateFacture($dateFacture)
            ->setNumero($numero)
            ->setFnpTraitee(true);

        /**
         * Un seul flush à la fin.
         */
        $this->comptabiliteService->enregistrer();
    }
}
