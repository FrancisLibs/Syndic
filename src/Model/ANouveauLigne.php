<?php

namespace App\Model;

use App\Entity\Compte;
use App\Entity\Coproprietaire;
use Symfony\Component\Validator\Constraints as Assert;

class ANouveauLigne
{
    #[Assert\NotNull(message: 'Le compte est obligatoire.')]
    private ?Compte $compte = null;

    private ?Coproprietaire $coproprietaire = null;

    #[Assert\NotBlank(message: 'Le solde est obligatoire.')]
    #[Assert\NotEqualTo(
        value: 0,
        message: 'Le solde ne peut pas être égal à zéro.'
    )]
    private ?string $solde = null;

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(?Compte $compte): self
    {
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

    public function setSolde(?string $solde): self
    {
        $this->solde = $solde;

        return $this;
    }

    public function getSoldeFloat(): float
    {
        if ($this->solde === null || $this->solde === '') {
            return 0.0;
        }

        return (float) str_replace(
            ',',
            '.',
            str_replace(' ', '', $this->solde)
        );
    }

    public function estDebit(): bool
    {
        return $this->getSoldeFloat() > 0;
    }

    public function estCredit(): bool
    {
        return $this->getSoldeFloat() < 0;
    }

    public function getMontantAbsolu(): float
    {
        return abs($this->getSoldeFloat());
    }
}
