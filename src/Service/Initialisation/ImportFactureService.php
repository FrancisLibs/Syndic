<?php

namespace App\Service\Initialisation;

use App\DTO\Initialisation\RapportImport;
use App\Entity\Imports\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use Doctrine\ORM\EntityManagerInterface;

final class ImportFactureService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    public function importer(
        RapportImport $rapport
    ): int {

        $nb = 0;

        foreach ($rapport->lignes as $ligne) {

            if (!$ligne->estValide()) {
                continue;
            }

            $import = new ImportFactureFournisseur();

            $import
                ->setDateFacture($ligne->dateFacture)
                ->setNumero($ligne->numero)
                ->setLibelle($ligne->libelle)
                ->setMontant($ligne->montant)
                ->setReglee($ligne->reglee)
                ->setDateReglement($ligne->dateReglement)
                ->setExercice($ligne->exercice)
                ->setFournisseur($ligne->fournisseur)
                ->setTypeCharge($ligne->typeCharge)
                ->setCoproprietaireAvanceur(
                    $ligne->coproprietaireAvanceur
                )
                ->setStatut(
                    ImportStatut::EN_ATTENTE
                );

            if (
                method_exists(
                    $import,
                    'setVolumeEau'
                )
            ) {
                $import->setVolumeEau(
                    $ligne->volumeEau
                );
            }

            $this->em->persist($import);

            ++$nb;
        }

        $this->em->flush();

        return $nb;
    }
}
