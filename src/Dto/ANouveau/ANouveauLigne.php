<?php

namespace App\Dto\ANouveau;

use App\Entity\Compte;
use App\Entity\Coproprietaire;
use Symfony\Component\Validator\Constraints as Assert;

final class ANouveauLigne
{
    #[Assert\NotNull(
        message: 'Le compte est obligatoire.'
    )]
    private ?Compte $compte = null;

    private ?Coproprietaire $coproprietaire = null;

    #[Assert\NotBlank(
        message: 'Le solde est obligatoire.'
    )]
    #[Assert\Regex(
        pattern: '/^-?\d+(?:[.,]\d{1,2})?$/',
        message: 'Le solde doit être un montant valide avec deux décimales au maximum.'
    )]
    private ?string $solde = null;

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(
        ?Compte $compte
    ): self {
        $this->compte = $compte;

        return $this;
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(
        ?Coproprietaire $coproprietaire
    ): self {
        $this->coproprietaire = $coproprietaire;

        return $this;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function setSolde(
        ?string $solde
    ): self {
        $this->solde = $solde;

        return $this;
    }

    public function getSoldeNormalise(): string
    {
        $solde = trim(
            (string) $this->solde
        );

        return str_replace(
            ',',
            '.',
            $solde
        );
    }

    public function getSoldeFloat(): float
    {
        return (float) $this->getSoldeNormalise();
    }

    public function estDebit(): bool
    {
        return $this->getSoldeFloat() > 0;
    }

    public function estCredit(): bool
    {
        return $this->getSoldeFloat() < 0;
    }

    public function getMontantAbsolu(): string
    {
        return number_format(
            abs($this->getSoldeFloat()),
            2,
            '.',
            ''
        );
    }
}
