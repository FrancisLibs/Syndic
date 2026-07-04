<?php

namespace App\Dto\Cloture;

use App\Entity\Compte;
use App\Entity\Coproprietaire;

final class SoldeReportable
{
    public function __construct(
        public readonly Compte $compte,
        public readonly ?Coproprietaire $coproprietaire,
        public readonly float $debit,
        public readonly float $credit,
    ) {}

    public function getSolde(): float
    {
        return $this->debit - $this->credit;
    }

    public function estDebiteur(): bool
    {
        return $this->debit > 0;
    }

    public function estCrediteur(): bool
    {
        return $this->credit > 0;
    }

    public function getSens(): string
    {
        return $this->debit > 0
            ? 'Débiteur'
            : 'Créditeur';
    }

    public function getMontant(): float
    {
        return max($this->debit, $this->credit);
    }
}
