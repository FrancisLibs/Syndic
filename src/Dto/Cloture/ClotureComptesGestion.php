<?php

namespace App\Dto\Cloture;

use App\Entity\Operation;

final readonly class ClotureComptesGestion
{
    /**
     * @param ClotureCompteGestionLigne[] $lignes
     */
    public function __construct(
        public array $lignes,
        public float $charges,
        public float $produits,
        public float $resultat,
        public bool $generee = false,
        public ?Operation $operation = null,
    ) {}

    public function estExcedent(): bool
    {
        return $this->resultat > 0.01;
    }

    public function estDeficit(): bool
    {
        return $this->resultat < -0.01;
    }
}
