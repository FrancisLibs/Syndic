<?php

namespace App\Dto\Eau;

use App\Entity\CompteurEau;
use App\Entity\Lot;

final class ConsommationCompteur
{
    public function __construct(
        private readonly CompteurEau $compteur,
        private readonly ?int $ancienIndex,
        private readonly ?int $nouvelIndex,
        private readonly ?int $consommation,
    ) {}

    public function getCompteur(): CompteurEau
    {
        return $this->compteur;
    }

    public function getAncienIndex(): ?int
    {
        return $this->ancienIndex;
    }

    public function getNouvelIndex(): ?int
    {
        return $this->nouvelIndex;
    }

    public function getConsommation(): ?int
    {
        return $this->consommation;
    }

    public function getReference(): string
    {
        return $this->compteur->getReference();
    }

    public function getLot(): ?Lot
    {
        return $this->compteur->getLot();
    }

    public function isComplete(): bool
    {
        return $this->ancienIndex !== null
            && $this->nouvelIndex !== null
            && $this->consommation !== null;
    }

    public function isCompteurGeneral(): bool
    {
        return $this->compteur->isGeneral();
    }

    public function isCompteurIndividuel(): bool
    {
        return !$this->isCompteurGeneral();
    }
}
