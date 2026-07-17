<?php

namespace App\Dto\Eau;

final class CalculEau
{
    /**
     * @param CalculEauLot[] $lots
     */
    public function __construct(
        private readonly float $montantTotalFactures,
        private readonly float $prixM3,
        private readonly int $consommationGenerale,
        private readonly int $consommationLots,
        private readonly int $consommationCommune,
        private readonly array $lots,
    ) {}

    public function getMontantTotalFactures(): float
    {
        return $this->montantTotalFactures;
    }

    public function getPrixM3(): float
    {
        return $this->prixM3;
    }

    public function getConsommationGenerale(): int
    {
        return $this->consommationGenerale;
    }

    public function getConsommationLots(): int
    {
        return $this->consommationLots;
    }

    public function getConsommationCommune(): int
    {
        return $this->consommationCommune;
    }

    /**
     * @return CalculEauLot[]
     */
    public function getLots(): array
    {
        return $this->lots;
    }

    public function getTotalPartIndividuelle(): float
    {
        $total = 0.0;

        foreach ($this->lots as $lot) {
            $total += $lot->getPartIndividuelle();
        }

        return round($total, 2);
    }

    public function getTotalPartCommune(): float
    {
        $total = 0.0;

        foreach ($this->lots as $lot) {
            $total += $lot->getPartCommune();
        }

        return round($total, 2);
    }

    public function getMontantTotalReparti(): float
    {
        $total = 0.0;

        foreach ($this->lots as $lot) {
            $total += $lot->getMontantTotal();
        }

        return round($total, 2);
    }

    public function getEcartArrondi(): float
    {
        return round(
            $this->montantTotalFactures
                - $this->getMontantTotalReparti(),
            2
        );
    }

    public function isEquilibre(): bool
    {
        return abs($this->getEcartArrondi()) < 0.01;
    }
}
