<?php

namespace App\Service\Imports;

use App\Entity\Exercice;
use App\Entity\Imports\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use App\Repository\Imports\ImportFactureFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ControleImportFactureFournisseurService
{
    public function __construct(
        private readonly ImportFactureFournisseurRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Contrôle toutes les lignes d’import d’un exercice.
     *
     * @return array{
     *     lignes: ImportFactureFournisseur[],
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
            /*
             * Une ligne déjà traitée ne doit plus être modifiée
             * par le service de contrôle.
             */
            if ($ligne->estTraitee()) {
                ++$nombreTraitees;

                $montantTotalCentimes +=
                    $this->montantEnCentimes(
                        $ligne->getMontant()
                    );

                continue;
            }

            /*
             * Une ligne restée en traitement signale généralement
             * un précédent traitement interrompu.
             */
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

            /*
             * Une ligne auparavant en erreur et désormais corrigée
             * repasse automatiquement en attente.
             */
            $ligne
                ->setStatut(ImportStatut::EN_ATTENTE)
                ->setErreur(null);

            ++$nombreEnAttente;

            $montantCentimes =
                $this->montantEnCentimes(
                    $ligne->getMontant()
                );

            $montantTotalCentimes += $montantCentimes;
            $montantAImporterCentimes += $montantCentimes;
        }

        /*
         * Ce compteur reste utile si une ligne a été placée
         * manuellement en traitement après la boucle.
         */
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
        ImportFactureFournisseur $ligne,
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

        $fournisseur = $ligne->getFournisseur();

        if ($fournisseur === null) {
            $erreurs[] =
                'Le fournisseur n’est pas renseigné.';
        } elseif ($fournisseur->getCompte() === null) {
            $erreurs[] = sprintf(
                'Aucun compte comptable n’est associé au fournisseur « %s ».',
                $fournisseur->getNom()
            );
        }

        $typeCharge = $ligne->getTypeCharge();

        if ($typeCharge === null) {
            $erreurs[] =
                'Le type de charge n’est pas renseigné.';
        } elseif ($typeCharge->getCompte() === null) {
            $erreurs[] = sprintf(
                'Aucun compte comptable n’est associé au type de charge « %s ».',
                $typeCharge->getNom()
            );
        }

        if ($ligne->getDateFacture() === null) {
            $erreurs[] =
                'La date de facture n’est pas renseignée.';
        } else {
            $dateFacture = $ligne->getDateFacture();

            if (
                $dateFacture < $exercice->getDateDebut()
                || $dateFacture > $exercice->getDateFin()
            ) {
                $erreurs[] = sprintf(
                    'La date de facture %s est en dehors de l’exercice.',
                    $dateFacture->format('d/m/Y')
                );
            }
        }

        $libelle = trim(
            (string) $ligne->getLibelle()
        );

        if ($libelle === '') {
            $erreurs[] =
                'Le libellé n’est pas renseigné.';
        }

        if (mb_strlen($libelle) > 255) {
            $erreurs[] =
                'Le libellé dépasse 255 caractères.';
        }

        $numero = trim(
            (string) $ligne->getNumero()
        );

        if (mb_strlen($numero) > 100) {
            $erreurs[] =
                'Le numéro de facture dépasse 100 caractères.';
        }

        $montant = $this->normaliserMontant(
            $ligne->getMontant()
        );

        if ($montant === null) {
            $erreurs[] =
                'Le montant n’est pas numérique.';
        } elseif ($montant <= 0) {
            $erreurs[] =
                'Le montant doit être supérieur à zéro.';
        }

        if (
            $ligne->isReglee()
            && $ligne->getDateReglement() === null
        ) {
            $erreurs[] =
                'La date de règlement est obligatoire pour une facture réglée.';
        }

        if (
            !$ligne->isReglee()
            && $ligne->getDateReglement() !== null
        ) {
            $erreurs[] =
                'Une date de règlement est renseignée alors que la facture n’est pas déclarée réglée.';
        }

        if (
            $ligne->getDateReglement() !== null
            && $ligne->getDateFacture() !== null
            && $ligne->getDateReglement()
            < $ligne->getDateFacture()
        ) {
            $erreurs[] =
                'La date de règlement est antérieure à la date de facture.';
        }

        if (
            $ligne->getVolumeEau() !== null
            && $ligne->getVolumeEau() < 0
        ) {
            $erreurs[] =
                'Le volume d’eau ne peut pas être négatif.';
        }

        $coproprietaireAvanceur =
            $ligne->getCoproprietaireAvanceur();

        if (
            $coproprietaireAvanceur !== null
            && $coproprietaireAvanceur->getCompte() === null
        ) {
            $erreurs[] =
                'Aucun compte comptable n’est associé au copropriétaire avanceur.';
        }

        if ($ligne->getFactureCreee() !== null) {
            $erreurs[] =
                'Cette ligne est déjà liée à une facture fournisseur.';
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
            $this->normaliserMontant($montant);

        if ($montantNormalise === null) {
            return 0;
        }

        return (int) round(
            $montantNormalise * 100
        );
    }
}
