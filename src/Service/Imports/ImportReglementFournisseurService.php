<?php

namespace App\Service\Imports;

use App\Entity\Exercice;
use App\Repository\Imports\ImportReglementFournisseurRepository;
use App\Service\ReglementFournisseurService;
use Doctrine\ORM\EntityManagerInterface;

final class ImportReglementFournisseurService
{
    public function __construct(
        private readonly ImportReglementFournisseurRepository $repository,
        private readonly ReglementFournisseurService $reglementService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function importer(
        Exercice $exercice
    ): void {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’importer des règlements dans un exercice clôturé.'
            );
        }

        $lignes = $this->repository
            ->findEnAttente($exercice);

        if ($lignes === []) {
            throw new \LogicException(
                'Aucun règlement fournisseur n’est en attente d’import.'
            );
        }

        foreach ($lignes as $ligne) {

            try {
                $ligne->marquerEnTraitement();

                $this->entityManager->flush();

                $fournisseur =
                    $ligne->getFournisseur();

                if ($fournisseur === null) {
                    throw new \LogicException(
                        'Le fournisseur est obligatoire.'
                    );
                }

                $dateReglement =
                    $ligne->getDateReglement();

                if ($dateReglement === null) {
                    throw new \LogicException(
                        'La date de règlement est obligatoire.'
                    );
                }

                $montant =
                    $ligne->getMontant();

                if ($montant === null) {
                    throw new \LogicException(
                        'Le montant du règlement est obligatoire.'
                    );
                }

                $reglement =
                    $this->reglementService
                    ->enregistrer(
                        fournisseur: $fournisseur,
                        exercice: $ligne->getExercice(),
                        dateReglement: $dateReglement,
                        montant: $montant,
                        reference: $ligne->getReference(),
                    );

                $ligne->marquerTraitee(
                    $reglement
                );
            } catch (\Throwable $exception) {

                $ligne->marquerErreur(
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        500
                    )
                );
            }
        }

        $this->entityManager->flush();
    }
}
