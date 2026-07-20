<?php

namespace App\Dto\Eau;

use App\Entity\Coproprietaire;
use App\Entity\Lot;

final class CalculEauLot
{
    public function __construct(
        private readonly Lot $lot,
        private readonly Coproprietaire $coproprietaire,
        private readonly int $consommation,
        private readonly float $partIndividuelle,
        private readonly float $partCommune,
        private readonly float $montantTotal,
    ) {}

    public function getLot(): Lot
    {
        return $this->lot;
    }

    public function getCoproprietaire(): Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function getReference(): ?string
    {
        return $this->lot->getReference();
    }

    public function getDesignation(): ?string
    {
        return $this->lot->getDesignation();
    }

    public function getConsommation(): int
    {
        return $this->consommation;
    }

    public function getPartIndividuelle(): float
    {
        return $this->partIndividuelle;
    }

    public function getPartCommune(): float
    {
        return $this->partCommune;
    }

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }
}
