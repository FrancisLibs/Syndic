<?php

namespace App\Dto\AssembleeGenerale;

use App\Entity\Coproprietaire;

final class SimulationRegularisationLigne
{
    public function __construct(
        public readonly Coproprietaire $coproprietaire,
        public readonly int $tantiemes,
        public readonly float $totalAppele,
        public readonly float $quotePartReelle,
    ) {}

    public function getRegularisation(): float
    {
        return round(
            $this->totalAppele - $this->quotePartReelle,
            2
        );
    }

    public function getMontant(): float
    {
        return abs($this->getRegularisation());
    }

    public function estCrediteur(): bool
    {
        return $this->getRegularisation() > 0.01;
    }

    public function estDebiteur(): bool
    {
        return $this->getRegularisation() < -0.01;
    }

    public function estEquilibre(): bool
    {
        return abs($this->getRegularisation()) < 0.01;
    }
}
