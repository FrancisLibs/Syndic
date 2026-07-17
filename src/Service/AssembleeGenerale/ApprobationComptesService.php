<?php

namespace App\Service\AssembleeGenerale;

use App\Dto\AssembleeGenerale\ApprobationComptes as ApprobationComptesDto;
use App\Dto\AssembleeGenerale\ApprobationLigne;
use App\Entity\ApprobationComptes as ApprobationComptesEntity;
use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Service\Cloture\SimulationRegularisationService;
use App\Service\ComptabiliteService;
use Doctrine\ORM\EntityManagerInterface;

final class ApprobationComptesService
{
    public function __construct(
        private SimulationRegularisationService $simulationService,
        private ComptabiliteService $comptabiliteService,
        private CompteRepository $compteRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function preparerApprobation(
        Exercice $exercice
    ): ApprobationComptesDto {
        $simulation =
            $this->simulationService
            ->simulerRegularisation($exercice);

        $lignes = [];

        foreach ($simulation->lignes as $ligne) {
            if ($ligne->estEquilibre()) {
                continue;
            }

            $lignes[] = new ApprobationLigne(
                coproprietaire: $ligne->coproprietaire,
                tantiemes: $ligne->tantiemes,
                totalAppele: $ligne->totalAppele,
                quotePartReelle: $ligne->quotePartReelle,
            );
        }

        return new ApprobationComptesDto(
            exercice: $exercice,
            lignes: $lignes,
        );
    }

    public function approuver(
        ApprobationComptesEntity $approbation
    ): Operation {
        $exercice = $approbation->getExercice();

        if (!$exercice) {
            throw new \LogicException(
                'Aucun exercice n’est associé à cette approbation.'
            );
        }

        if (!$exercice->isCloture()) {
            throw new \LogicException(
                'Les comptes ne peuvent être approuvés que pour un exercice clôturé.'
            );
        }

        if ($approbation->getOperation()) {
            throw new \LogicException(
                'Les comptes de cet exercice ont déjà été approuvés.'
            );
        }

        $dateAssembleeGenerale =
            $approbation->getDateAssembleeGenerale();

        if (!$dateAssembleeGenerale) {
            throw new \LogicException(
                'La date de l’Assemblée Générale est obligatoire.'
            );
        }

        $numeroResolution =
            trim((string) $approbation->getNumeroResolution());

        if ($numeroResolution === '') {
            throw new \LogicException(
                'Le numéro de résolution est obligatoire.'
            );
        }

        $compte489000 =
            $this->compteRepository->findOneBy([
                'numero' => '489000',
            ]);

        if (!$compte489000) {
            throw new \LogicException(
                'Le compte 489000 est introuvable.'
            );
        }

        $preparation =
            $this->preparerApprobation($exercice);

        if ($preparation->getNombreLignes() === 0) {
            throw new \LogicException(
                'Aucune régularisation ne doit être comptabilisée.'
            );
        }

        $operation =
            $this->comptabiliteService->creerOperation(
                $dateAssembleeGenerale,
                sprintf(
                    'Approbation des comptes %s - %s',
                    $exercice->getNom(),
                    $numeroResolution
                ),
                OperationType::APPROBATION_COMPTES,
                $numeroResolution
            );

        foreach ($preparation->lignes as $ligne) {
            if ($ligne->estEquilibre()) {
                continue;
            }

            $coproprietaire =
                $ligne->coproprietaire;

            $compteCoproprietaire =
                $coproprietaire->getCompte();

            if (!$compteCoproprietaire) {
                throw new \LogicException(
                    sprintf(
                        'Aucun compte comptable n’est associé au copropriétaire %s.',
                        (string) $coproprietaire
                    )
                );
            }

            $montant = $ligne->getMontant();

            if ($ligne->estCrediteur()) {
                /*
                 * Le copropriétaire a trop versé :
                 *
                 * Débit  489000
                 * Crédit compte copropriétaire
                 */
                $this->comptabiliteService->creerDebit(
                    $operation,
                    $exercice,
                    $compte489000,
                    $montant
                );

                $this->comptabiliteService->creerCredit(
                    $operation,
                    $exercice,
                    $compteCoproprietaire,
                    $montant,
                    $coproprietaire
                );

                continue;
            }

            /*
             * Le copropriétaire n’a pas assez versé :
             *
             * Débit  compte copropriétaire
             * Crédit 489000
             */
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
                $compte489000,
                $montant
            );
        }


        $approbation->setOperation($operation);

        $this->comptabiliteService->enregistrer($operation);

        $this->entityManager->flush();

        return $operation;
    }
}
