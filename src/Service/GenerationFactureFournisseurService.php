<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\FactureFournisseurType;
use App\Enum\OperationType;
use App\Enum\CompteType;
use App\Repository\CompteRepository;
use App\Service\Travaux\GenerationRealisationChantierService;
use App\Service\Travaux\CoutReelChantierService;


class GenerationFactureFournisseurService
{
    public function __construct(
        private GenerationRepartitionService $generationRepartitionService,
        private ComptabiliteService $comptabiliteService,
        private CompteRepository $compteRepository,
        private GenerationRealisationChantierService $generationRealisationChantierService,
        private readonly CoutReelChantierService $coutReelChantierService,
    ) {}

    public function generer(
        FactureFournisseur $facture
    ): void {
        $typeCharge = $facture->getTypeCharge();
        $chantier = $facture->getChantier();

        // Contrôle du montant autorisé pour le chantier
        if ($chantier !== null) {

            if ($chantier->isCloture()) {
                throw new \LogicException(
                    'Impossible de comptabiliser une facture sur un chantier clôturé.'
                );
            }

            $montantAutorise = $chantier->getMontantTotalVote();

            $coutActuel = $this->coutReelChantierService
                ->calculer($chantier, $facture);

            $montantFacture = (float) $facture->getMontant();

            if ($facture->getType() === FactureFournisseurType::AVOIR) {
                $montantFacture *= -1;
            }

            $nouveauCout = round(
                $coutActuel + $montantFacture,
                2
            );

            if ($nouveauCout > $montantAutorise + 0.009) {

                throw new \LogicException(sprintf(
                    'Impossible de comptabiliser cette facture : '
                        . 'le coût du chantier atteindrait %s €, '
                        . 'alors que le montant total voté est de %s €. '
                        . 'Un vote complémentaire est nécessaire.',
                    number_format($nouveauCout, 2, ',', ' '),
                    number_format($montantAutorise, 2, ',', ' ')
                ));
            }
        }

        if ($chantier === null && $typeCharge === null) {
            throw new \LogicException(
                'La facture doit être rattachée à un type de charge ou à un chantier.'
            );
        }

        /*
        * Une facture doit concerner soit :
        * - une charge courante ;
        * - un chantier.
        */
        if ($chantier !== null) {

            if (
                $chantier->estFinanceParAppels()
                && $typeCharge !== null
            ) {
                throw new \LogicException(
                    'Une facture de chantier financé par appels spécifiques '
                        . 'ne doit pas avoir de type de charge.'
                );
            }

            if (
                $chantier->estFinanceParChargesCourantes()
                && $typeCharge === null
            ) {
                throw new \LogicException(
                    'Un chantier financé par charges courantes '
                        . 'nécessite un type de charge.'
                );
            }
        }

        /*
        * Détermination du compte de charge.
        */
        if (
            $chantier !== null
            && $chantier->estFinanceParAppels()
        ) {
            // Travaux financés par appels spécifiques
            $compteCharge = $chantier->getCompteCharge();
        } else {
            // Charges courantes, avec ou sans chantier
            $compteCharge = $typeCharge?->getCompte();
        }

        if ($compteCharge === null) {
            throw new \LogicException(
                'Le compte de charge est introuvable.'
            );
        }

        if ($compteCharge->getType() !== CompteType::CHARGE) {
            throw new \LogicException(sprintf(
                'Le compte %s (%s) ne peut pas être utilisé '
                    . 'pour comptabiliser une facture fournisseur : '
                    . 'ce n’est pas un compte de charges.',
                $compteCharge->getNumero(),
                $compteCharge->getLibelle()
            ));
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

        /**
         * Une FNP :
         * - doit être une facture, pas un avoir ;
         * - ne peut pas avoir été avancée par un copropriétaire.
         */
        if ($facture->isFnp()) {

            if (
                $facture->getType()
                !== FactureFournisseurType::FACTURE
            ) {
                throw new \LogicException(
                    'Une FNP ne peut pas être enregistrée comme un avoir.'
                );
            }

            if ($coproprietaireAvanceur !== null) {
                throw new \LogicException(
                    'Une FNP ne peut pas être associée à un copropriétaire avanceur.'
                );
            }
        }

        /**
         * Compte de contrepartie :
         *
         * Facture normale :
         * Crédit compte fournisseur
         *
         * FNP :
         * Crédit 408000
         */
        $compteContrepartie = $compteFournisseur;

        if ($facture->isFnp()) {
            $compteContrepartie =
                $this->compteRepository
                ->findByNumeroOrFail('408000');
        }

        /**
         * 1. Comptabilisation de la facture,
         * de l'avoir ou de la FNP.
         */
        $operationFacture =
            $this->comptabiliteService
            ->creerOperation(
                $facture->getDateFacture(),
                $facture->getLibelle(),
                $facture->isFnp()
                    ? OperationType::FNP
                    : OperationType::CHARGE,
                $facture->getNumero()
            );

        $operationFacture
            ->setTypeCharge($typeCharge)
            ->setFournisseur($fournisseur);

        if (
            $facture->getType()
            === FactureFournisseurType::AVOIR
        ) {

            /** $typeCharge
             * Avoir fournisseur
             *
             * Débit  : compte fournisseur
             * Crédit : compte de charge
             */
            $this->comptabiliteService->creerDebit(
                $operationFacture,
                $facture->getExercice(),
                $compteFournisseur,
                $facture->getMontant()
            );

            $ecritureCharge =
                $this->comptabiliteService->creerCredit(
                    $operationFacture,
                    $facture->getExercice(),
                    $compteCharge,
                    $facture->getMontant()
                );
        } else {

            /**
             * Facture normale :
             *
             * Débit  charge
             * Crédit fournisseur
             *
             * FNP :
             *
             * Débit  charge
             * Crédit 408000
             */
            $ecritureCharge =
                $this->comptabiliteService->creerDebit(
                    $operationFacture,
                    $facture->getExercice(),
                    $compteCharge,
                    $facture->getMontant()
                );

            $this->comptabiliteService->creerCredit(
                $operationFacture,
                $facture->getExercice(),
                $compteContrepartie,
                $facture->getMontant()
            );
        }

        $this->comptabiliteService->enregistrer();

        /**
         * 2. Transfert éventuel de la dette
         * vers un copropriétaire.
         *
         * Ne concerne jamais une FNP.
         */
        if (
            !$facture->isFnp()
            && $coproprietaireAvanceur !== null
            && $compteCoproprietaire !== null
        ) {
            $operationTransfert =
                $this->comptabiliteService
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

            if (
                $facture->getType()
                === FactureFournisseurType::AVOIR
            ) {
                /**
                 * Avoir concernant une dépense avancée
                 *
                 * Débit  copropriétaire
                 * Crédit fournisseur
                 */
                $this->comptabiliteService->creerDebit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteCoproprietaire,
                    $facture->getMontant(),
                    $coproprietaireAvanceur
                );

                $this->comptabiliteService->creerCredit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteFournisseur,
                    $facture->getMontant()
                );
            } else {
                /**
                 * Facture concernant une dépense avancée
                 *
                 * Débit  fournisseur
                 * Crédit copropriétaire
                 */
                $this->comptabiliteService->creerDebit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteFournisseur,
                    $facture->getMontant()
                );

                $this->comptabiliteService->creerCredit(
                    $operationTransfert,
                    $facture->getExercice(),
                    $compteCoproprietaire,
                    $facture->getMontant(),
                    $coproprietaireAvanceur
                );
            }

            $this->comptabiliteService->enregistrer();
        }

        $facture
            ->setOperation($operationFacture)
            ->setComptabilisee(true);

        /**
         * 3. Répartition de la charge.
         *
         * La FNP est bien une charge de l'exercice :
         * elle doit donc être répartie normalement.
         *
         * Charges courantes :
         * répartition habituelle entre copropriétaires.
         *
         * Chantiers financés par appels spécifiques :
         * aucune répartition supplémentaire.
         */
        if (
            ($chantier === null || $chantier->estFinanceParChargesCourantes())
            && $typeCharge !== null
            && !$typeCharge->isEau()
        ) {
            $montantARepartir =
                (float) $facture->getMontant();

            if (
                $facture->getType()
                === FactureFournisseurType::AVOIR
            ) {
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

        /**
         * 4. Réalisation des travaux.
         *
         * Uniquement pour les chantiers
         * financés par appels spécifiques.
         */
        if (
            $chantier !== null
            && $chantier->estFinanceParAppels()
        ) {
            $this->generationRealisationChantierService
                ->generer($facture);
        }
    }
}
