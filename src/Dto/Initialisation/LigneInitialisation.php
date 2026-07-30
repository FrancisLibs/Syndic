<?php

namespace App\Dto\Initialisation;

use App\Entity\Compte;
use App\Entity\Coproprietaire;

final class LigneInitialisation
{
    public function __construct(
        private readonly Compte $compte,
        private readonly float $debit = 0.0,
        private readonly float $credit = 0.0,
        private readonly ?Coproprietaire $coproprietaire = null,
        private readonly ?string $libelle = null,
    ) {
        if ($debit < 0) {
            throw new \InvalidArgumentException(
                'Le montant au débit ne peut pas être négatif.'
            );
        }

        if ($credit < 0) {
            throw new \InvalidArgumentException(
                'Le montant au crédit ne peut pas être négatif.'
            );
        }

        if ($debit > 0 && $credit > 0) {
            throw new \InvalidArgumentException(
                'Une ligne ne peut pas comporter simultanément un débit et un crédit.'
            );
        }
    }

    public static function debit(
        Compte $compte,
        float $montant,
        ?Coproprietaire $coproprietaire = null,
        ?string $libelle = null,
    ): self {
        return new self(
            compte: $compte,
            debit: round($montant, 2),
            credit: 0.0,
            coproprietaire: $coproprietaire,
            libelle: $libelle,
        );
    }

    public static function credit(
        Compte $compte,
        float $montant,
        ?Coproprietaire $coproprietaire = null,
        ?string $libelle = null,
    ): self {
        return new self(
            compte: $compte,
            debit: 0.0,
            credit: round($montant, 2),
            coproprietaire: $coproprietaire,
            libelle: $libelle,
        );
    }

    public function getCompte(): Compte
    {
        return $this->compte;
    }

    public function getNumeroCompte(): string
    {
        return $this->compte->getNumero();
    }

    public function getLibelleCompte(): string
    {
        return $this->compte->getLibelle();
    }

    public function getLibelle(): string
    {
        return $this->libelle
            ?? $this->compte->getLibelle();
    }

    public function getDebit(): float
    {
        return round($this->debit, 2);
    }

    public function getCredit(): float
    {
        return round($this->credit, 2);
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function estDebit(): bool
    {
        return $this->debit > 0;
    }

    public function estCredit(): bool
    {
        return $this->credit > 0;
    }

    public function estVide(): bool
    {
        return $this->debit === 0.0
            && $this->credit === 0.0;
    }
}
