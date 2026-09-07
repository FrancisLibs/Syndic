<?php

namespace App\Service\Imports;

use App\Dto\Imports\LigneImportReglement;
use App\Dto\Imports\RapportImport;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Enum\ReglementFournisseurType;

final class AnalyseImportReglementService
{
    private const NOMBRE_COLONNES = 6;

    public function __construct(
        private readonly ExerciceRepository $exerciceRepository,
        private readonly FournisseurRepository $fournisseurRepository,
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

        /** @var array<int, \App\Entity\Exercice> */
        $exercicesRencontres = [];

        /** @var array<int, LigneImportReglement[]> */
        $lignesParExercice = [];

        try {
            $this->ignorerBomUtf8($handle);

            $avecEntete =
                $this->lirePremiereLigne($handle);

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
                'Le fichier ne contient aucun règlement.'
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

        $premiereColonne =
            isset($premiereLigne[0])
            ? strtolower(trim($premiereLigne[0]))
            : '';

        $avecEntete =
            $premiereColonne === 'date_reglement';

        if (!$avecEntete) {
            rewind($handle);
        }

        return $avecEntete;
    }

    private function analyserLigne(
        array $csv,
        int $numero
    ): LigneImportReglement {
        $ligne = new LigneImportReglement();
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
            $dateReglement,
            $montant,
            $reference,
            $exerciceId,
            $fournisseurId,
            $type,
        ] = array_map(
            static fn(mixed $valeur): string =>
            trim((string) $valeur),
            $csv
        );

        $this->analyserDateReglement(
            $ligne,
            $dateReglement
        );

        $this->analyserMontant(
            $ligne,
            $montant
        );

        $this->analyserReference(
            $ligne,
            $reference
        );

        $this->analyserExercice(
            $ligne,
            $exerciceId
        );

        $this->analyserFournisseur(
            $ligne,
            $fournisseurId
        );

        $this->analyserType(
            $ligne,
            $type
        );

        return $ligne;
    }

    private function analyserDateReglement(
        LigneImportReglement $ligne,
        string $dateReglement
    ): void {
        if ($dateReglement === '') {
            $ligne->ajouterErreur(
                'La date de règlement est obligatoire.'
            );

            return;
        }

        $ligne->dateReglement =
            $this->convertirDate($dateReglement);

        if ($ligne->dateReglement === null) {
            $ligne->ajouterErreur(
                'Date de règlement invalide.'
            );
        }
    }

    private function analyserMontant(
        LigneImportReglement $ligne,
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

    private function analyserReference(
        LigneImportReglement $ligne,
        string $reference
    ): void {
        $ligne->reference =
            $reference !== ''
            ? $reference
            : null;

        if (
            $ligne->reference !== null
            && mb_strlen($ligne->reference) > 100
        ) {
            $ligne->ajouterErreur(
                'La référence dépasse 100 caractères.'
            );
        }
    }

    private function analyserExercice(
        LigneImportReglement $ligne,
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
        LigneImportReglement $ligne,
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

        if ($fournisseur->getCompte() === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Aucun compte comptable n’est associé au fournisseur « %s ».',
                    $fournisseur->getNom()
                )
            );
        }

        $ligne->fournisseurId =
            $fournisseur->getId();

        $ligne->fournisseur =
            $fournisseur->getNom();

        $ligne->fournisseurEntite =
            $fournisseur;
    }

    private function analyserType(
        LigneImportReglement $ligne,
        string $type
    ): void {
        $type = strtolower(
            trim($type)
        );

        if ($type === '') {
            $ligne->ajouterErreur(
                'Le type de règlement est obligatoire.'
            );

            return;
        }

        $typeEnum =
            ReglementFournisseurType::tryFrom(
                $type
            );

        if ($typeEnum === null) {
            $ligne->ajouterErreur(
                sprintf(
                    'Type de règlement invalide : "%s". Valeurs autorisées : paiement, remboursement.',
                    $type
                )
            );

            return;
        }

        $ligne->type = $typeEnum;
    }

    private function convertirIdentifiantObligatoire(
        LigneImportReglement $ligne,
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

        $id = filter_var(
            $valeur,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($id === false) {
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

    /**
     * @param array<int, \App\Entity\Exercice> $exercicesRencontres
     * @param array<int, LigneImportReglement[]> $lignesParExercice
     */
    private function enregistrerExerciceRencontre(
        LigneImportReglement $ligne,
        array &$exercicesRencontres,
        array &$lignesParExercice
    ): void {
        if ($ligne->exerciceEntite === null) {
            return;
        }

        $exercice =
            $ligne->exerciceEntite;

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
     * @param array<int, LigneImportReglement[]> $lignesParExercice
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
                'Le fichier contient plusieurs exercices : %s. Tous les règlements d’un même import doivent appartenir au même exercice.',
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
            $date =
                \DateTimeImmutable::createFromFormat(
                    $format,
                    $valeur
                );

            $erreurs =
                \DateTimeImmutable::getLastErrors();

            if ($date === false) {
                continue;
            }

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
