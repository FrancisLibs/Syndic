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
