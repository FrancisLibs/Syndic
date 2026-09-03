<?php

namespace App\Service\Imports;

use App\Entity\Exercice;
use App\Entity\Imports\ImportReglementFournisseur;
use App\Enum\ImportStatut;
use App\Repository\Imports\ImportReglementFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ControleImportReglementFournisseurService
{
    public function __construct(
        private readonly ImportReglementFournisseurRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     lignes: ImportReglementFournisseur[],
     *     nombreLignes: int,
     *     nombreEnAttente: int,
     *     nombreEnTraitement: int,
     *     nombreTraitees: int,
     *     nombreErreurs: int,
     *     montantTotal: float,
     *     montantAImporter: float,
     *     peutImporter: bool
     * }
     */
    public function controler(
        Exercice $exercice
    ): array {
        $lignes = $this->repository
            ->findPourExercice($exercice);

        $nombreEnAttente = 0;
        $nombreEnTraitement = 0;
        $nombreTraitees = 0;
        $nombreErreurs = 0;

        $montantTotalCentimes = 0;
        $montantAImporterCentimes = 0;

        foreach ($lignes as $ligne) {

            if ($ligne->estTraitee()) {
                ++$nombreTraitees;

                $montantTotalCentimes +=
                    $this->montantEnCentimes(
                        $ligne->getMontant()
                    );

                continue;
            }

            if ($ligne->estEnTraitement()) {
                $ligne->marquerErreur(
                    'Le précédent traitement de cette ligne a été interrompu.'
                );
            }

            $erreurs = $this->controlerLigne(
                $ligne,
                $exercice
            );

            if ($erreurs !== []) {
                $ligne->marquerErreur(
                    implode(' ', $erreurs)
                );

                ++$nombreErreurs;

                continue;
            }

            $ligne
                ->setStatut(ImportStatut::EN_ATTENTE)
                ->setErreur(null);

            ++$nombreEnAttente;

            $montantCentimes =
                $this->montantEnCentimes(
                    $ligne->getMontant()
                );

            $montantTotalCentimes +=
                $montantCentimes;

            $montantAImporterCentimes +=
                $montantCentimes;
        }

        foreach ($lignes as $ligne) {
            if ($ligne->estEnTraitement()) {
                ++$nombreEnTraitement;
            }
        }

        $this->entityManager->flush();

        return [
            'lignes' => $lignes,
            'nombreLignes' => count($lignes),
            'nombreEnAttente' => $nombreEnAttente,
            'nombreEnTraitement' => $nombreEnTraitement,
            'nombreTraitees' => $nombreTraitees,
            'nombreErreurs' => $nombreErreurs,
            'montantTotal' =>
            $montantTotalCentimes / 100,
            'montantAImporter' =>
            $montantAImporterCentimes / 100,
            'peutImporter' =>
            $nombreEnAttente > 0
                && $nombreErreurs === 0
                && $nombreEnTraitement === 0,
        ];
    }

    /**
     * @return string[]
     */
    private function controlerLigne(
        ImportReglementFournisseur $ligne,
        Exercice $exercice
    ): array {
        $erreurs = [];

        if ($ligne->getExercice() === null) {
            $erreurs[] =
                'L’exercice n’est pas renseigné.';
        } elseif (
            $ligne->getExercice()->getId()
            !== $exercice->getId()
        ) {
            $erreurs[] =
                'La ligne appartient à un autre exercice.';
        }

        if ($exercice->isCloture()) {
            $erreurs[] =
                'L’exercice est clôturé.';
        }

        $fournisseur =
            $ligne->getFournisseur();

        if ($fournisseur === null) {
            $erreurs[] =
                'Le fournisseur n’est pas renseigné.';
        } elseif ($fournisseur->getCompte() === null) {
            $erreurs[] = sprintf(
                'Aucun compte comptable n’est associé au fournisseur « %s ».',
                $fournisseur->getNom()
            );
        }

        $dateReglement =
            $ligne->getDateReglement();

        if ($dateReglement === null) {
            $erreurs[] =
                'La date de règlement n’est pas renseignée.';
        } elseif (
            $dateReglement < $exercice->getDateDebut()
            || $dateReglement > $exercice->getDateFin()
        ) {
            $erreurs[] = sprintf(
                'La date de règlement %s ne correspond pas à la période de l’exercice.',
                $dateReglement->format('d/m/Y')
            );
        }

        $montant =
            $this->normaliserMontant(
                $ligne->getMontant()
            );

        if ($montant === null) {
            $erreurs[] =
                'Le montant n’est pas numérique.';
        } elseif ($montant <= 0) {
            $erreurs[] =
                'Le montant doit être supérieur à zéro.';
        }

        $reference = trim(
            (string) $ligne->getReference()
        );

        if (mb_strlen($reference) > 100) {
            $erreurs[] =
                'La référence dépasse 100 caractères.';
        }

        if ($ligne->getReglementCree() !== null) {
            $erreurs[] =
                'Cette ligne est déjà liée à un règlement fournisseur.';
        }

        return $erreurs;
    }

    private function normaliserMontant(
        float|string|null $montant
    ): ?float {
        if ($montant === null) {
            return null;
        }

        if (is_string($montant)) {
            $montant = str_replace(
                [' ', ','],
                ['', '.'],
                trim($montant)
            );
        }

        if (
            $montant === ''
            || !is_numeric($montant)
        ) {
            return null;
        }

        return round(
            (float) $montant,
            2
        );
    }

    private function montantEnCentimes(
        float|string|null $montant
    ): int {
        $montantNormalise =
            $this->normaliserMontant(
                $montant
            );

        if ($montantNormalise === null) {
            return 0;
        }

        return (int) round(
            $montantNormalise * 100
        );
    }
}
