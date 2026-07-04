<?php

namespace App\Dto\Cloture;

final readonly class ResultatExercice
{
    public function __construct(
        public float $charges,
        public float $produits,
        public float $resultat,
    ) {}

    public function estExcedentaire(): bool
    {
        return $this->resultat > 0.01;
    }

    public function estDeficitaire(): bool
    {
        return $this->resultat < -0.01;
    }

    public function estEquilibre(): bool
    {
        return abs($this->resultat) < 0.01;
    }
}
