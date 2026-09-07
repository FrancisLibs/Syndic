<?php

namespace App\Service\Imports;

use App\Dto\Imports\LigneImportReglement;
use App\Dto\Imports\RapportImport;
use App\Entity\Imports\ImportReglementFournisseur;
use App\Enum\ImportStatut;
use App\Repository\Imports\ImportReglementFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ImportCsvReglementFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImportReglementFournisseurRepository $repository,
    ) {}

    public function importer(
        RapportImport $rapport
    ): int {
        if (!$rapport->estValide()) {
            throw new \LogicException(
                'Le rapport contient des erreurs. L’import est impossible.'
            );
        }

        $nbImportes = 0;
        $nbDoublons = 0;

        foreach ($rapport->lignesValides() as $ligne) {

            if (!$ligne instanceof LigneImportReglement) {
                continue;
            }

            $import = $this->creerImport(
                $ligne
            );

            if (
                $this->repository
                ->trouverDoublon($import)
            ) {
                ++$nbDoublons;

                continue;
            }

            $this->em->persist(
                $import
            );

            ++$nbImportes;
        }

        if ($nbImportes > 0) {
            $this->em->flush();
        }

        return $nbImportes;
    }

    private function creerImport(
        LigneImportReglement $ligne
    ): ImportReglementFournisseur {
        if ($ligne->dateReglement === null) {
            throw new \LogicException(
                sprintf(
                    'La date de règlement est absente à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        if ($ligne->montant === null) {
            throw new \LogicException(
                sprintf(
                    'Le montant est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        if ($ligne->type === null) {
            throw new \LogicException(
                sprintf(
                    'Le type de règlement est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        if ($ligne->exerciceEntite === null) {
            throw new \LogicException(
                sprintf(
                    'L’exercice est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        if ($ligne->fournisseurEntite === null) {
            throw new \LogicException(
                sprintf(
                    'Le fournisseur est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        $import =
            new ImportReglementFournisseur();

        $import
            ->setDateReglement(
                $ligne->dateReglement
            )
            ->setMontant(
                number_format(
                    $ligne->montant,
                    2,
                    '.',
                    ''
                )
            )
            ->setType(
                $ligne->type
            )
            ->setReference(
                $ligne->reference
            )
            ->setExercice(
                $ligne->exerciceEntite
            )
            ->setFournisseur(
                $ligne->fournisseurEntite
            )
            ->setStatut(
                ImportStatut::EN_ATTENTE
            )
            ->setErreur(null);

        return $import;
    }
}
