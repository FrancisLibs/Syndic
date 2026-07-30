<?php

namespace App\Dto\Imports;

final class RapportImport
{
    /**
     * @var LigneImportFacture[]
     */
    private array $lignes = [];

    public function ajouter(
        LigneImportFacture $ligne
    ): void {
        $this->lignes[] = $ligne;
    }

    /**
     * @return LigneImportFacture[]
     */
    public function lignesValides(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(LigneImportFacture $ligne) => $ligne->estValide()
            )
        );
    }

    /**
     * @return LigneImportFacture[]
     */
    public function lignesEnErreur(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(LigneImportFacture $ligne) => !$ligne->estValide()
            )
        );
    }

    public function total(): int
    {
        return count($this->lignes);
    }

    public function nbValides(): int
    {
        return count($this->lignesValides());
    }

    public function nbErreurs(): int
    {
        return count($this->lignesEnErreur());
    }

    public function estValide(): bool
    {
        return $this->nbErreurs() === 0;
    }

    /**
     * @return LigneImportFacture[]
     */
    public function getLignes(): array
    {
        return $this->lignes;
    }
}
