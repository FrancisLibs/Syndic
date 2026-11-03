<?php

namespace App\Service\Travaux;

use App\Entity\Exercice;
use App\Entity\Travaux\Chantier;
use App\Enum\OperationStatut;
use App\Repository\FactureFournisseurRepository;
use App\Repository\ExerciceRepository;
use App\Enum\OperationType;
use App\Service\ComptabiliteService;
use Doctrine\ORM\EntityManagerInterface;

final class ClotureChantierService
{
    public function __construct(
        private readonly CoutReelChantierService $coutReelService,
        private readonly SimulationClotureChantierService $simulationService,
        private readonly ExerciceRepository $exerciceRepository,
        private readonly ComptabiliteService $comptabiliteService,
        private readonly EntityManagerInterface $entityManager,
        private readonly FactureFournisseurRepository $factureRepository,
    ) {}

    public function verifier(
        Chantier $chantier,
        ?\DateTimeImmutable $dateRegularisation = null,
    ): array {
        if ($chantier->isCloture()) {
            throw new \LogicException(
                'Ce chantier est déjà clôturé.'
            );
        }

        if ($chantier->getOperationRegularisation() !== null) {
            throw new \LogicException(
                'Une régularisation comptable existe déjà pour ce chantier.'
            );
        }

        // Vérifier qu'aucune facture du chantier
        // ne reste en attente de comptabilisation.
        $facturesNonComptabilisees = array_filter(
            $this->factureRepository
                ->findNonComptabiliseesPourChantier($chantier),

            static function ($facture): bool {

                $operation = $facture->getOperation();

                // Une facture annulée ne bloque pas la clôture.
                if (
                    $operation !== null
                    && $operation->getStatut() === OperationStatut::ANNULE
                ) {
                    return false;
                }

                // Les autres factures non comptabilisées
                // doivent continuer à bloquer la clôture.
                return true;
            }
        );

        if ($facturesNonComptabilisees !== []) {
            throw new \LogicException(sprintf(
                'Impossible de clôturer ce chantier : '
                    . '%d facture(s) ne sont pas comptabilisées.',
                count($facturesNonComptabilisees)
            ));
        }

        if ($chantier->estFinanceParChargesCourantes()) {

            if (!$chantier->getAppels()->isEmpty()) {
                throw new \LogicException(
                    'Ce chantier est financé par les charges courantes, '
                        . 'mais possède des appels de fonds spécifiques.'
                );
            }

            if ($dateRegularisation !== null) {
                $this->verifierExercice($dateRegularisation);
            }

            $factures = $this->factureRepository
                ->findPourChantier($chantier);

            foreach ($factures as $facture) {

                $operation = $facture->getOperation();

                // Ignorer les factures annulées
                if (
                    $operation !== null
                    && $operation->getStatut() === OperationStatut::ANNULE
                ) {
                    continue;
                }

                if (
                    $operation === null
                    || $operation->getStatut() !== OperationStatut::VALIDE
                    || !$operation->isEquilibree()
                ) {
                    throw new \LogicException(
                        sprintf(
                            'La facture %s ne possède pas une opération comptable valide et équilibrée.',
                            $facture->getNumero() ?? '#' . $facture->getId()
                        )
                    );
                }
            }

            return [
                'totalAppels' => 0.0,
                'coutReel' => $this->coutReelService->calculer($chantier),
                'regularisationTotale' => 0.0,
                'lignes' => [],
            ];
        }

        if ($chantier->getCompteProvision() === null) {
            throw new \LogicException(
                'Le compte de provision du chantier est absent.'
            );
        }

        if (
            $chantier->getCompteCharge() === null
            || $chantier->getCompteProduit() === null
        ) {
            throw new \LogicException(
                'Les comptes comptables du chantier sont incomplets.'
            );
        }

        if ($dateRegularisation !== null) {
            $this->verifierExercice($dateRegularisation);
        }

        foreach ($chantier->getAppels() as $appel) {

            $operation = $appel->getOperation();

            if ($operation === null) {
                throw new \LogicException(
                    sprintf(
                        'L’appel de fonds %s n’est pas comptabilisé.',
                        $appel->getNumero()
                    )
                );
            }

            if ($operation->getStatut() !== OperationStatut::VALIDE) {
                throw new \LogicException(
                    sprintf(
                        'L’opération comptable de l’appel %s n’est pas valide.',
                        $appel->getNumero()
                    )
                );
            }

            if (!$operation->isEquilibree()) {
                throw new \LogicException(
                    sprintf(
                        'L’opération comptable de l’appel %s est déséquilibrée.',
                        $appel->getNumero()
                    )
                );
            }
        }

        $coutReel = $this->coutReelService->calculer($chantier);

        if ($coutReel > $chantier->getMontantTotalVote() + 0.009) {
            throw new \LogicException(
                'Le coût réel du chantier dépasse le montant total voté. '
                    . 'Un vote complémentaire est nécessaire avant la clôture.'
            );
        }

        $simulation = $this->simulationService->simuler(
            $chantier,
            $coutReel
        );

        $totalAppels = $simulation['totalAppels'];
        $regularisation = $simulation['regularisationTotale'];

        if (abs(
            ($totalAppels - $coutReel) - $regularisation
        ) >= 0.01) {
            throw new \LogicException(
                'La simulation de clôture est déséquilibrée.'
            );
        }

        return $simulation;
    }

    private function verifierExercice(
        \DateTimeImmutable $dateRegularisation
    ): Exercice {

        $exercice = $this->exerciceRepository->findPourDate(
            $dateRegularisation
        );

        if ($exercice === null) {
            throw new \LogicException(
                'Aucun exercice comptable ne correspond à la date de régularisation.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'L’exercice comptable est clôturé.'
            );
        }

        if (!$exercice->isActif()) {
            throw new \LogicException(
                'L’exercice comptable n’est pas actif.'
            );
        }

        return $exercice;
    }

    public function cloturer(
        Chantier $chantier,
        \DateTimeImmutable $dateRegularisation
    ): void {

        $this->entityManager->wrapInTransaction(
            function () use ($chantier, $dateRegularisation): void {

                $simulation = $this->verifier(
                    $chantier,
                    $dateRegularisation
                );

                if ($chantier->estFinanceParChargesCourantes()) {

                    $chantier
                        ->setDateCloture($dateRegularisation)
                        ->setCloture(true);

                    $this->entityManager->flush();

                    return;
                }

                $exercice = $this->verifierExercice(
                    $dateRegularisation
                );

                $compteProvision = $chantier->getCompteProvision();

                // Préparation des régularisations à comptabiliser
                $regularisations = [];
                $totalCentimes = 0;

                foreach ($simulation['lignes'] as $ligne) {

                    $montantCentimes = (int) round(
                        $ligne['regularisation'] * 100
                    );

                    if ($montantCentimes === 0) {
                        continue;
                    }

                    $coproprietaire = $ligne['coproprietaire'];
                    $compteCoproprietaire = $coproprietaire->getCompte();

                    if ($compteCoproprietaire === null) {
                        throw new \LogicException(
                            sprintf(
                                'Aucun compte comptable pour %s.',
                                $coproprietaire
                            )
                        );
                    }

                    $regularisations[] = [
                        'coproprietaire' => $coproprietaire,
                        'compte' => $compteCoproprietaire,
                        'montantCentimes' => $montantCentimes,
                    ];

                    $totalCentimes += $montantCentimes;
                }

                // Vérification du total
                if (
                    $totalCentimes !==
                    (int) round(
                        $simulation['regularisationTotale'] * 100
                    )
                ) {
                    throw new \LogicException(
                        'Le total des régularisations individuelles est incorrect.'
                    );
                }

                // Création uniquement si des écritures sont nécessaires
                if ($regularisations !== []) {

                    $operation = $this->comptabiliteService->creerOperation(
                        $dateRegularisation,
                        'Régularisation travaux - ' . $chantier->getLibelle(),
                        OperationType::REGULARISATION_TRAVAUX,
                        'TRAV-' . $chantier->getId()
                    );

                    foreach ($regularisations as $ligne) {

                        $montantCentimes = $ligne['montantCentimes'];
                        $montant = abs($montantCentimes) / 100;

                        $coproprietaire = $ligne['coproprietaire'];
                        $compteCoproprietaire = $ligne['compte'];

                        if ($montantCentimes > 0) {

                            // Excédent : crédit au copropriétaire
                            $this->comptabiliteService->creerDebit(
                                $operation,
                                $exercice,
                                $compteProvision,
                                $montant
                            );

                            $this->comptabiliteService->creerCredit(
                                $operation,
                                $exercice,
                                $compteCoproprietaire,
                                $montant,
                                $coproprietaire
                            );
                        } else {

                            // Déficit : débit au copropriétaire
                            $this->comptabiliteService->creerDebit(
                                $operation,
                                $exercice,
                                $compteCoproprietaire,
                                $montant,
                                $coproprietaire
                            );

                            $this->comptabiliteService->creerCredit(
                                $operation,
                                $exercice,
                                $compteProvision,
                                $montant
                            );
                        }
                    }

                    // Vérification de l'équilibre comptable
                    $operation->valider();

                    $chantier->setOperationRegularisation($operation);
                }

                // Clôture définitive du chantier
                $chantier
                    ->setDateCloture($dateRegularisation)
                    ->setCloture(true);

                $this->entityManager->flush();
            }
        );
    }
}
