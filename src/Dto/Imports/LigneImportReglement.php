<?php

namespace App\Dto\Imports;

use App\Entity\Exercice;
use App\Entity\Fournisseur;
use App\Enum\ReglementFournisseurType;

final class LigneImportReglement
{
    public int $ligne = 0;

    public ?\DateTimeImmutable $dateReglement = null;

    public ?float $montant = null;

    public ?string $reference = null;

    public ?ReglementFournisseurType $type = null;

    public ?Exercice $exerciceEntite = null;

    public ?Fournisseur $fournisseurEntite = null;

    // ===========================
    // Identifiants
    // ===========================

    public ?int $exerciceId = null;

    public ?int $fournisseurId = null;

    // ===========================
    // Libellés d'affichage
    // ===========================

    public string $exercice = '';

    public string $fournisseur = '';

    /**
     * @var string[]
     */
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
