<?php

namespace App\Dto\Cloture;

use App\Entity\Operation;

final readonly class ANouveaux
{
    /**
     * @param SoldeReportable[] $lignes
     */
    public function __construct(
        public array $lignes,
        public float $totalDebit,
        public float $totalCredit,
        public bool $generes = false,
        public ?Operation $operation = null,
    ) {}

    public function estEquilibre(): bool
    {
        return abs($this->totalDebit - $this->totalCredit) < 0.01;
    }
}
