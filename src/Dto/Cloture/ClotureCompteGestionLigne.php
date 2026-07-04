<?php

namespace App\Dto\Cloture;

use App\Entity\Compte;

final readonly class ClotureCompteGestionLigne
{
    public function __construct(
        public Compte $compte,
        public float $debit,
        public float $credit,
    ) {}
}
