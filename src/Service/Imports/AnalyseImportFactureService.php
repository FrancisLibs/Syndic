<?php

namespace App\Service\Imports;

use App\Dto\Imports\LigneImportFacture;
use App\Dto\Imports\RapportImport;
use App\Repository\CoproprietaireRepository;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Repository\TypeChargeRepository;

final class AnalyseImportFactureService
{
    private const NOMBRE_COLONNES = 10;

    public function __construct(
        private readonly ExerciceRepository $exerciceRepository,
        private readonly FournisseurRepository $fournisseurRepository,
        private readonly TypeChargeRepository $typeChargeRepository,
        private readonly CoproprietaireRepository $coproprietaireRepository,
    ) {}

    public function analyser(
        string $nomFichier
    ): RapportImport {

        if (!is_file($nomFichier)) {
            throw new \RuntimeException(
                sprintf(
                    'Le fichier "%s" est introuvable.',
                    $nomFichier
                )
            );
        }

        $handle = fopen(
            $nomFichier,
            'rb'
        );

        if ($handle === false) {
            throw new \RuntimeException(
                'Impossible de lire le fichier.'
            );
        }

        $rapport = new RapportImport();

        /**
         * @var array<int, \App\Entity\Exercice>
         */
        $exercicesRencontres = [];

        /**
         * @var array<int, LigneImportFacture[]>
         */
        $lignesParExercice = [];

        try {
            $this->ignorerBomUtf8($handle);

            $avecEntete = $this->lirePremiereLigne(
                $handle
            );

            $numeroLigne = $avecEntete ? 2 : 1;

            while (
                ($csv = fgetcsv(
                    $handle,
                    separator: ';'
                )) !== false
            ) {
                if ($this->ligneVide($csv)) {
                    ++$numeroLigne;

                    continue;
                }

                $ligne = $this->analyserLigne(
                    $csv,
                    $numeroLigne
                );

                $rapport->ajouter($ligne);

                $this->enregistrerExerciceRencontre(
                    $ligne,
                    $exercicesRencontres,
                    $lignesParExercice
                );

                ++$numeroLigne;
            }
        } finally {
            fclose($handle);
        }

        $this->verifierExerciceUnique(
            $rapport,
            $exercicesRencontres,
            $lignesParExercice
        );

        if ($rapport->total() === 0) {
            $rapport->ajouterErreurGlobale(
                'Le fichier ne contient aucune facture.'
            );
        }

        return $rapport;
    }

    /**
     * @param resource $handle
     */
    private function ignorerBomUtf8(
        $handle
    ): void {

        $bom = fread(
            $handle,
            3
        );

        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
    }

    /**
     * @param resource $handle
     */
    private function lirePremiereLigne(
        $handle
    ): bool {

        $premiereLigne = fgetcsv(
            $handle,
            separator: ';'
        );

        if ($premiereLigne === false) {
            throw new \RuntimeException(
                'Le fichier est vide.'
            );
        }

        $premiereColonne = isset($premiereLigne[0])
            ? strtolower(trim($premiereLigne[0]))
            : '';

        $avecEntete =
            $premiereColonne === 'date_facture';

        if (!$avecEntete) {
            rewind($handle);
        }

        return $avecEntete;
    }

    private function analyserLigne(
        array $csv,
        int $numero
    ): LigneImportFacture {

        $ligne = new LigneImportFacture();
        $ligne->ligne = $numero;

        if (count($csv) !== self::NOMBRE_COLONNES) {
            $ligne->ajouterErreur(
                sprintf(
                    '%d colonnes trouvées (%d attendues).',
                    count($csv),
                    self::NOMBRE_COLONNES
                )
            );

            return $ligne;
        }

        [
            $dateFacture,
            $numeroFacture,
            $libelle,
            $montant,
            $exerciceId,
            $fournisseurId,
            $typeChargeId,
            $avanceurId,
            $statut,
            $volumeEau
        ] = array_map(
            static fn(mixed $valeur): string =>
            trim((string) $valeur),
            $csv
        );

        /**
         * La colonne statut est présente dans le CSV,
         * mais le statut de l'entité d'import sera fixé
         * à EN_ATTENTE lors de l'import.
         */
        unset($statut);

        $this->analyserDateFacture(
            $ligne,
            $dateFacture
        );

        $this->analyserNumero(
            $ligne,
            $numeroFacture
        );

        $this->analyserLibelle(
            $ligne,
            $libelle
        );

        $this->analyserMontant(
            $ligne,
            $montant
        );

        $this->analyserExercice(
            $ligne,
            $exerciceId
        );

        $this->analyserFournisseur(
            $ligne,
            $fournisseurId
        );

        $this->analyserTypeCharge(
            $ligne,
            $typeChargeId
        );

        $this->analyserAvanceur(
            $ligne,
            $avanceurId
        );

        $this->analyserVolumeEau(
            $ligne,
            $volumeEau
        );

        return $ligne;
    }

    private function analyserDateFacture(
        LigneImportFacture $ligne,
        string $dateFacture
    ): void {

        if ($dateFacture === '') {
            $ligne->ajouterErreur(
                'La date de facture est obligatoire.'
            );

            return;
        }

        $ligne->dateFacture =
            $this->convertirDate($dateFacture);

        if ($ligne->dateFacture === null) {
            $ligne->ajouterErreur(
                'Date de facture invalide.'
            );
        }
    }

    private function analyserNumero(
        LigneImportFacture $ligne,
        string $numeroFacture
    ): void {

        $ligne->numero =
            $numeroFacture !== ''
            ? $numeroFacture
            : null;
    }

    private function analyserLibelle(
        LigneImportFacture $ligne,
        string $libelle
    ): void {

        $ligne->libelle = $libelle;

        if ($libelle === '') {
            $ligne->ajouterErreur(
                'Le libellé est obligatoire.'
            );
        }
    }

    private function analyserMontant(
        LigneImportFacture $ligne,
        string $montant
    ): void {

        if ($montant === '') {
            $ligne->ajouterErreur(
                'Le montant est obligatoire.'
            );

            return;
        }

        $montantNormalise = str_replace(
            [' ', ','],
            ['', '.'],
            $montant
        );

        if (!is_numeric($montantNormalise)) {
            $ligne->ajouterErreur(
                'Montant invalide.'
            );

            return;
        }

        $ligne->montant =
            (float) $montantNormalise;

        if ($ligne->montant <= 0) {
            $ligne->ajouterErreur(
                'Le montant doit être supérieur à zéro.'
            );
        }
    }

    private function analyserExercice(
        LigneImportFacture $ligne,
        string $exerciceId
    ): void {

        $id = $this->convertirIdentifiantObligatoire(
            $ligne,
            $exerciceId,
            'L’exercice'
        );

        if ($id === null) {
            return;
        }

        $exercice =
            $this->exerciceRepository->find($id);

        if ($exercice === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Exercice inconnu : identifiant %d.',
                    $id
                )
            );

            return;
        }

        $ligne->exerciceId =
            $exercice->getId();

        $ligne->exercice =
            $exercice->getNom();

        $ligne->exerciceEntite =
            $exercice;
    }

    private function analyserFournisseur(
        LigneImportFacture $ligne,
        string $fournisseurId
    ): void {

        $id = $this->convertirIdentifiantObligatoire(
            $ligne,
            $fournisseurId,
            'Le fournisseur'
        );

        if ($id === null) {
            return;
        }

        $fournisseur =
            $this->fournisseurRepository->find($id);

        if ($fournisseur === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Fournisseur inconnu : identifiant %d.',
                    $id
                )
            );

            return;
        }

        $ligne->fournisseurId =
            $fournisseur->getId();

        $ligne->fournisseur =
            $fournisseur->getNom();

        $ligne->fournisseurEntite =
            $fournisseur;
    }

    private function analyserTypeCharge(
        LigneImportFacture $ligne,
        string $typeChargeId
    ): void {

        $id = $this->convertirIdentifiantObligatoire(
            $ligne,
            $typeChargeId,
            'Le type de charge'
        );

        if ($id === null) {
            return;
        }

        $typeCharge =
            $this->typeChargeRepository->find($id);

        if ($typeCharge === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Type de charge inconnu : identifiant %d.',
                    $id
                )
            );

            return;
        }

        $ligne->typeChargeId =
            $typeCharge->getId();

        $ligne->typeCharge =
            $typeCharge->getNom();

        $ligne->typeChargeEntite =
            $typeCharge;
    }

    private function analyserAvanceur(
        LigneImportFacture $ligne,
        string $avanceurId
    ): void {

        if ($avanceurId === '') {
            $ligne->coproprietaireAvanceurId =
                null;

            $ligne->coproprietaireAvanceur =
                null;

            $ligne->coproprietaireAvanceurEntite =
                null;

            return;
        }

        $id = $this->convertirIdentifiant(
            $avanceurId
        );

        if ($id === null) {
            $ligne->ajouterErreur(
                'L’identifiant du copropriétaire avanceur est invalide.'
            );

            return;
        }

        $coproprietaire =
            $this->coproprietaireRepository->find($id);

        if ($coproprietaire === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Copropriétaire avanceur inconnu : identifiant %d.',
                    $id
                )
            );

            return;
        }

        $ligne->coproprietaireAvanceurId =
            $coproprietaire->getId();

        $ligne->coproprietaireAvanceur =
            (string) $coproprietaire;

        $ligne->coproprietaireAvanceurEntite =
            $coproprietaire;
    }

    private function analyserVolumeEau(
        LigneImportFacture $ligne,
        string $volumeEau
    ): void {

        if ($volumeEau === '') {
            $ligne->volumeEau = null;

            return;
        }

        $volumeNormalise = str_replace(
            [' ', ','],
            ['', '.'],
            $volumeEau
        );

        if (!is_numeric($volumeNormalise)) {
            $ligne->ajouterErreur(
                'Le volume d’eau est invalide.'
            );

            return;
        }

        $volume = (float) $volumeNormalise;

        if ($volume < 0) {
            $ligne->ajouterErreur(
                'Le volume d’eau ne peut pas être négatif.'
            );

            return;
        }

        $ligne->volumeEau = $volume;
    }

    private function convertirIdentifiantObligatoire(
        LigneImportFacture $ligne,
        string $valeur,
        string $libelle
    ): ?int {

        if ($valeur === '') {
            $ligne->ajouterErreur(
                sprintf(
                    '%s est obligatoire.',
                    $libelle
                )
            );

            return null;
        }

        $id = $this->convertirIdentifiant(
            $valeur
        );

        if ($id === null) {
            $ligne->ajouterErreur(
                sprintf(
                    '%s possède un identifiant invalide.',
                    $libelle
                )
            );

            return null;
        }

        return $id;
    }

    private function convertirIdentifiant(
        string $valeur
    ): ?int {

        $id = filter_var(
            $valeur,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        return $id === false
            ? null
            : $id;
    }

    /**
     * @param array<int, \App\Entity\Exercice> $exercicesRencontres
     * @param array<int, LigneImportFacture[]> $lignesParExercice
     */
    private function enregistrerExerciceRencontre(
        LigneImportFacture $ligne,
        array &$exercicesRencontres,
        array &$lignesParExercice
    ): void {

        if ($ligne->exerciceEntite === null) {
            return;
        }

        $exercice = $ligne->exerciceEntite;
        $id = $exercice->getId();

        if ($id === null) {
            return;
        }

        $exercicesRencontres[$id] =
            $exercice;

        $lignesParExercice[$id][] =
            $ligne;
    }

    /**
     * @param array<int, \App\Entity\Exercice> $exercicesRencontres
     * @param array<int, LigneImportFacture[]> $lignesParExercice
     */
    private function verifierExerciceUnique(
        RapportImport $rapport,
        array $exercicesRencontres,
        array $lignesParExercice
    ): void {

        if (count($exercicesRencontres) === 1) {
            $exercice = reset(
                $exercicesRencontres
            );

            if ($exercice !== false) {
                $rapport->setExercice(
                    $exercice
                );
            }

            return;
        }

        if (count($exercicesRencontres) <= 1) {
            return;
        }

        $nomsExercices = array_map(
            static fn(\App\Entity\Exercice $exercice): string =>
            $exercice->getNom(),
            $exercicesRencontres
        );

        $rapport->ajouterErreurGlobale(
            sprintf(
                'Le fichier contient plusieurs exercices : %s. Toutes les factures d’un même import doivent appartenir au même exercice.',
                implode(', ', $nomsExercices)
            )
        );

        foreach ($lignesParExercice as $lignes) {
            foreach ($lignes as $ligne) {
                $ligne->ajouterErreur(
                    'Cette ligne appartient à un fichier contenant plusieurs exercices.'
                );
            }
        }
    }

    private function convertirDate(
        string $valeur
    ): ?\DateTimeImmutable {

        foreach (
            [
                '!d/m/Y',
                '!d/m/y',
                '!Y-m-d',
            ] as $format
        ) {
            $date = \DateTimeImmutable::createFromFormat(
                $format,
                $valeur
            );

            $erreurs =
                \DateTimeImmutable::getLastErrors();

            if ($date === false) {
                continue;
            }

            /*
             * getLastErrors() retourne false lorsqu’il n’y a
             * ni avertissement ni erreur.
             */
            if (
                $erreurs !== false
                && (
                    $erreurs['warning_count'] > 0
                    || $erreurs['error_count'] > 0
                )
            ) {
                continue;
            }

            return $date;
        }

        return null;
    }

    private function ligneVide(
        array $ligne
    ): bool {

        foreach ($ligne as $colonne) {
            if (trim((string) $colonne) !== '') {
                return false;
            }
        }

        return true;
    }
}
