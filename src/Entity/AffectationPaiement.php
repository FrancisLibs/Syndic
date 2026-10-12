<?php

namespace App\Entity;

use App\Entity\Travaux\Chantier;
use App\Repository\AffectationPaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationPaiementRepository::class)]
class AffectationPaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Paiement $paiement = null;

    // Appel de fonds pour charges courantes
    #[ORM\ManyToOne(inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?LigneAppelFond $ligneAppel = null;

    // Chantier de travaux
    #[ORM\ManyToOne(targetEntity: Chantier::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Chantier $chantier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    public function getLigneAppel(): ?LigneAppelFond
    {
        return $this->ligneAppel;
    }

    public function setLigneAppel(?LigneAppelFond $ligneAppel): static
    {
        $this->ligneAppel = $ligneAppel;

        if ($ligneAppel !== null) {
            $this->chantier = null;
        }

        return $this;
    }

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): static
    {
        $this->chantier = $chantier;

        if ($chantier !== null) {
            $this->ligneAppel = null;
        }

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }
}
