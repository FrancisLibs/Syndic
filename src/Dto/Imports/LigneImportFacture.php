<?php

namespace App\Dto\Imports;

final class LigneImportFacture
{
    public int $ligne = 0;

    public ?\DateTimeImmutable $dateFacture = null;

    public ?string $numero = null;

    public string $libelle = '';

    public ?float $montant = null;

    public bool $reglee = false;

    public ?\DateTimeImmutable $dateReglement = null;

    // ===========================
    // Identifiants
    // ===========================

    public ?int $exerciceId = null;

    public ?int $fournisseurId = null;

    public ?int $typeChargeId = null;

    public ?int $coproprietaireAvanceurId = null;

    // ===========================
    // Libellés (affichage)
    // ===========================

    public string $exercice = '';

    public string $fournisseur = '';

    public string $typeCharge = '';

    public ?string $coproprietaireAvanceur = null;

    // ===========================

    public ?float $volumeEau = null;

    /** @var string[] */
    public array $erreurs = [];

    public function estValide(): bool
    {
        return count($this->erreurs) === 0;
    }

    public function ajouterErreur(string $erreur): void
    {
        $this->erreurs[] = $erreur;
    }
}
