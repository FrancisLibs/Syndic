<?php

namespace App\Service\Imports;

use App\Entity\Exercice;
use App\Repository\Imports\ImportFactureFournisseurRepository;
use App\Service\CreationFactureFournisseurService;
use Doctrine\ORM\EntityManagerInterface;

final class ImportFactureFournisseurService
{
    public function __construct(
        private readonly ImportFactureFournisseurRepository $repository,
        private readonly CreationFactureFournisseurService $creationService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function importer(
        Exercice $exercice
    ): void {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’importer des factures dans un exercice clôturé.'
            );
        }

        $lignes = $this->repository
            ->findEnAttente($exercice);

        if ($lignes === []) {
            throw new \LogicException(
                'Aucune facture fournisseur n’est en attente d’import.'
            );
        }

        foreach ($lignes as $ligne) {
            try {
                $ligne->marquerEnTraitement();
                $this->entityManager->flush();

                $facture = $this->creationService
                    ->creerEtComptabiliser(
                        exercice: $ligne->getExercice(),
                        fournisseur: $ligne->getFournisseur(),
                        typeCharge: $ligne->getTypeCharge(),
                        dateFacture: $ligne->getDateFacture(),
                        numero: $ligne->getNumero(),
                        libelle: $ligne->getLibelle(),
                        montant: $ligne->getMontant(),
                        coproprietaireAvanceur: $ligne->getCoproprietaireAvanceur(),
                        volumeEau: $ligne->getVolumeEau(),
                    );

                $ligne->marquerTraitee(
                    $facture
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
