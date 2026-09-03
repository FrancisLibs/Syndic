<?php

namespace App\Dto\Imports;

use App\Entity\Exercice;

final class RapportImport
{
    /**
     * @var array<int, LigneImportFacture|LigneImportReglement>
     */
    private array $lignes = [];

    /**
     * @var string[]
     */
    private array $erreursGlobales = [];

    private ?Exercice $exercice = null;

    public function ajouter(
        LigneImportFacture|LigneImportReglement $ligne
    ): void {
        $this->lignes[] = $ligne;
    }

    /**
     * @return array<int, LigneImportFacture|LigneImportReglement>
     */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    /**
     * @return array<int, LigneImportFacture|LigneImportReglement>
     */
    public function lignesValides(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(
                    LigneImportFacture|LigneImportReglement $ligne
                ): bool => $ligne->estValide()
            )
        );
    }

    /**
     * @return array<int, LigneImportFacture|LigneImportReglement>
     */
    public function lignesEnErreur(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(
                    LigneImportFacture|LigneImportReglement $ligne
                ): bool => !$ligne->estValide()
            )
        );
    }

    public function total(): int
    {
        return count($this->lignes);
    }

    public function nbValides(): int
    {
        return count(
            $this->lignesValides()
        );
    }

    public function nbErreurs(): int
    {
        return count(
            $this->lignesEnErreur()
        );
    }

    public function setExercice(
        Exercice $exercice
    ): self {
        $this->exercice = $exercice;

        return $this;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function ajouterErreurGlobale(
        string $erreur
    ): void {
        if (
            !in_array(
                $erreur,
                $this->erreursGlobales,
                true
            )
        ) {
            $this->erreursGlobales[] =
                $erreur;
        }
    }

    /**
     * @return string[]
     */
    public function getErreursGlobales(): array
    {
        return $this->erreursGlobales;
    }

    public function aDesErreursGlobales(): bool
    {
        return $this->erreursGlobales !== [];
    }

    public function estValide(): bool
    {
        return $this->total() > 0
            && !$this->aDesErreursGlobales()
            && $this->nbErreurs() === 0
            && $this->exercice !== null;
    }
}
