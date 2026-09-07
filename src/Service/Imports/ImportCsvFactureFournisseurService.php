<?php

namespace App\Service\Imports;

use App\Dto\Imports\LigneImportFacture;
use App\Dto\Imports\RapportImport;
use App\Entity\Imports\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use App\Repository\Imports\ImportFactureFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ImportCsvFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImportFactureFournisseurRepository $repository,
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

            $import = $this->creerImport($ligne);

            if ($this->repository->trouverDoublon($import)) {
                ++$nbDoublons;
                continue;
            }

            $this->em->persist($import);

            ++$nbImportes;
        }

        if ($nbImportes > 0) {
            $this->em->flush();
        }

        return $nbImportes;
    }

    private function creerImport(
        LigneImportFacture $ligne
    ): ImportFactureFournisseur {

        if ($ligne->dateFacture === null) {
            throw new \LogicException(
                sprintf(
                    'La date de facture est absente à la ligne %d.',
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

        if ($ligne->typeChargeEntite === null) {
            throw new \LogicException(
                sprintf(
                    'Le type de charge est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        if ($ligne->type === null) {
            throw new \LogicException(
                sprintf(
                    'Le type de document est absent à la ligne %d.',
                    $ligne->ligne
                )
            );
        }

        $import = new ImportFactureFournisseur();

        $import
            ->setDateFacture($ligne->dateFacture)
            ->setNumero($ligne->numero)
            ->setLibelle($ligne->libelle)
            ->setMontant(
                number_format(
                    $ligne->montant,
                    2,
                    '.',
                    ''
                )
            )
            ->setType($ligne->type)
            ->setExercice($ligne->exerciceEntite)
            ->setFournisseur($ligne->fournisseurEntite)
            ->setTypeCharge($ligne->typeChargeEntite)
            ->setCoproprietaireAvanceur(
                $ligne->coproprietaireAvanceurEntite
            )
            ->setStatut(ImportStatut::EN_ATTENTE)
            ->setErreur(null);

        if (method_exists($import, 'setVolumeEau')) {
            $import->setVolumeEau(
                $ligne->volumeEau
            );
        }

        return $import;
    }
}
