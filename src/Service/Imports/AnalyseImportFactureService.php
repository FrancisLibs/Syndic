<?php

namespace App\Service\Imports;

use App\DTO\Initialisation\LigneImportFacture;
use App\DTO\Initialisation\RapportImport;
use App\Repository\CoproprietaireRepository;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Repository\TypeChargeRepository;

final class AnalyseImportFactureService
{
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

        // UTF8 BOM

        $bom = fread($handle, 3);

        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Première ligne

        $premiereLigne = fgetcsv(
            $handle,
            separator: ';'
        );

        if ($premiereLigne === false) {

            fclose($handle);

            throw new \RuntimeException(
                'Le fichier est vide.'
            );
        }

        $avecEntete =
            strtolower(trim($premiereLigne[0]))
            === 'date_facture';

        if (!$avecEntete) {
            rewind($handle);
        }

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

            $rapport->ajouter(
                $this->analyserLigne(
                    $csv,
                    $numeroLigne
                )
            );

            ++$numeroLigne;
        }

        fclose($handle);

        return $rapport;
    }

    private function analyserLigne(
        array $csv,
        int $numero
    ): LigneImportFacture {

        $ligne = new LigneImportFacture();

        $ligne->ligne = $numero;

        if (count($csv) !== 12) {

            $ligne->ajouterErreur(
                sprintf(
                    '%d colonnes trouvées (12 attendues)',
                    count($csv)
                )
            );

            return $ligne;
        }

        [
            $dateFacture,
            $numeroFacture,
            $libelle,
            $montant,
            $reglee,
            $dateReglement,
            $exerciceId,
            $fournisseurId,
            $typeChargeId,
            $avanceurId,
            $statut,
            $volumeEau
        ] = array_map(
            'trim',
            $csv
        );

        // ===========================
        // Date facture
        // ===========================

        $ligne->dateFacture = $this->date($dateFacture);

        if ($ligne->dateFacture === null) {
            $ligne->ajouterErreur('Date de facture invalide.');
        }

        // ===========================
        // Numéro
        // ===========================

        $ligne->numero = $numeroFacture !== ''
            ? $numeroFacture
            : null;

        // ===========================
        // Libellé
        // ===========================

        $ligne->libelle = $libelle;

        if ($ligne->libelle === '') {
            $ligne->ajouterErreur('Le libellé est obligatoire.');
        }

        // ===========================
        // Montant
        // ===========================

        $ligne->montant = (float) str_replace(
            ',',
            '.',
            $montant
        );

        if ($ligne->montant <= 0) {
            $ligne->ajouterErreur('Montant invalide.');
        }

        // ===========================
        // Réglée
        // ===========================

        $ligne->reglee = in_array(
            strtoupper($reglee),
            [
                '1',
                'OUI',
                'TRUE',
                'VRAI'
            ],
            true
        );

        // ===========================
        // Date règlement
        // ===========================

        if ($dateReglement !== '') {

            $ligne->dateReglement = $this->date(
                $dateReglement
            );

            if ($ligne->dateReglement === null) {
                $ligne->ajouterErreur(
                    'Date de règlement invalide.'
                );
            }
        }

        // ===========================
        // Exercice
        // ===========================

        if ($exerciceId !== '') {

            $exercice = $this->exerciceRepository
                ->find((int) $exerciceId);

            if ($exercice) {

                $ligne->exerciceId = $exercice->getId();
                $ligne->exercice = $exercice->getNom();
            } else {

                $ligne->ajouterErreur(
                    'Exercice inconnu.'
                );
            }
        }

        // ===========================
        // Fournisseur
        // ===========================

        if ($fournisseurId !== '') {

            $fournisseur = $this->fournisseurRepository
                ->find((int) $fournisseurId);

            if ($fournisseur) {

                $ligne->fournisseurId = $fournisseur->getId();
                $ligne->fournisseur = $fournisseur->getNom();
            } else {

                $ligne->ajouterErreur(
                    'Fournisseur inconnu.'
                );
            }
        }

        // ===========================
        // Type de charge
        // ===========================

        if ($typeChargeId !== '') {

            $typeCharge = $this->typeChargeRepository
                ->find((int) $typeChargeId);

            if ($typeCharge) {

                $ligne->typeChargeId = $typeCharge->getId();
                $ligne->typeCharge = $typeCharge->getNom();
            } else {

                $ligne->ajouterErreur(
                    'Type de charge inconnu.'
                );
            }
        }

        // ===========================
        // Copropriétaire avanceur
        // ===========================

        if ($avanceurId !== '') {

            $coproprietaire =
                $this->coproprietaireRepository
                ->find((int) $avanceurId);

            if ($coproprietaire) {

                $ligne->coproprietaireAvanceurId =
                    $coproprietaire->getId();

                $ligne->coproprietaireAvanceur =
                    (string) $coproprietaire;
            } else {

                $ligne->ajouterErreur(
                    'Copropriétaire avanceur inconnu.'
                );
            }
        }

        // ===========================
        // Volume eau
        // ===========================

        if ($volumeEau !== '') {

            $ligne->volumeEau = (float) str_replace(
                ',',
                '.',
                $volumeEau
            );
        }

        return $ligne;
    }

    private function date(
        string $date
    ): ?\DateTimeImmutable {

        foreach (
            [
                'd/m/Y',
                'd/m/y',
                'Y-m-d'
            ] as $format
        ) {

            $d = \DateTimeImmutable::createFromFormat(
                $format,
                $date
            );

            if ($d !== false) {
                return $d;
            }
        }

        return null;
    }

    private function ligneVide(
        array $ligne
    ): bool {

        foreach ($ligne as $colonne) {

            if (trim($colonne) !== '') {
                return false;
            }
        }

        return true;
    }
}
