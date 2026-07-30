<?php

namespace App\Service\Imports;

use App\DTO\Imports\LigneImportFacture;
use App\DTO\Imports\RapportImport;
use App\Entity\Imports\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use App\Repository\CoproprietaireRepository;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Repository\TypeChargeRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ImportCsvFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExerciceRepository $exerciceRepository,
        private readonly FournisseurRepository $fournisseurRepository,
        private readonly TypeChargeRepository $typeChargeRepository,
        private readonly CoproprietaireRepository $coproprietaireRepository,
    ) {
    }

    public function importer(
        RapportImport $rapport
    ): int {

        $nb = 0;

        foreach ($rapport->lignesValides() as $ligne) {

            $this->em->persist(
                $this->creerImport($ligne)
            );

            ++$nb;
        }

        if ($nb > 0) {
            $this->em->flush();
        }

        return $nb;
    }

    private function creerImport(
        LigneImportFacture $ligne
    ): ImportFactureFournisseur {

        $import = new ImportFactureFournisseur();

        $import
            ->setDateFacture(
                $ligne->dateFacture
            )
            ->setNumero(
                $ligne->numero
            )
            ->setLibelle(
                $ligne->libelle
            )
            ->setMontant(
                number_format(
                    $ligne->montant,
                    2,
                    '.',
                    ''
                )
            )
            ->setReglee(
                $ligne->reglee
            )
            ->setDateReglement(
                $ligne->dateReglement
            )
            ->setExercice(
                $this->trouverExercice(
                    $ligne->exerciceId
                )
            )
            ->setFournisseur(
                $this->trouverFournisseur(
                    $ligne->fournisseurId
                )
            )
            ->setTypeCharge(
                $this->trouverTypeCharge(
                    $ligne->typeChargeId
                )
            )
            ->setCoproprietaireAvanceur(
                $this->trouverCoproprietaireNullable(
                    $ligne->coproprietaireAvanceurId
                )
            )
            ->setStatut(
                ImportStatut::EN_ATTENTE
            )
            ->setErreur(null);

        if (method_exists($import, 'setVolumeEau')) {

            $import->setVolumeEau(
                $ligne->volumeEau
            );

        }

        return $import;
    }

    private function trouverExercice(
        ?int $id
    ): \App\Entity\Exercice {
        if ($id === null || $id <= 0) {
            throw new \InvalidArgumentException(
                'L’exercice est obligatoire.'
            );
        }

        $exercice = $this->exerciceRepository->find($id);

        if ($exercice === null) {
            throw new \InvalidArgumentException(
                sprintf('Exercice %d introuvable.', $id)
            );
        }

        return $exercice;
    }

    private function trouverFournisseur(
        ?int $id
    ): \App\Entity\Fournisseur {
        if ($id === null || $id <= 0) {
            throw new \InvalidArgumentException(
                'Le fournisseur est obligatoire.'
            );
        }

        $fournisseur = $this->fournisseurRepository->find($id);

        if ($fournisseur === null) {
            throw new \InvalidArgumentException(
                sprintf('Fournisseur %d introuvable.', $id)
            );
        }

        return $fournisseur;
    }

    private function trouverTypeCharge(
        ?int $id
    ): \App\Entity\TypeCharge {
        if ($id === null || $id <= 0) {
            throw new \InvalidArgumentException(
                'Le type de charge est obligatoire.'
            );
        }

        $typeCharge = $this->typeChargeRepository->find($id);

        if ($typeCharge === null) {
            throw new \InvalidArgumentException(
                sprintf('Type de charge %d introuvable.', $id)
            );
        }

        return $typeCharge;
    }

    private function trouverCoproprietaireNullable(
        ?int $id
    ): ?\App\Entity\Coproprietaire {
        if ($id === null) {
            return null;
        }

        if ($id <= 0) {
            throw new \InvalidArgumentException(
                'L’identifiant du copropriétaire avanceur est incorrect.'
            );
        }

        $coproprietaire =
            $this->coproprietaireRepository->find($id);

        if ($coproprietaire === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Copropriétaire avanceur %d introuvable.',
                    $id
                )
            );
        }

        return $coproprietaire;
    }
}