<?php

namespace App\Entity;

use App\Repository\AffectationReglementFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(
    repositoryClass: AffectationReglementFournisseurRepository::class
)]
class AffectationReglementFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        inversedBy: 'affectationsReglement'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?FactureFournisseur $facture = null;

    #[ORM\ManyToOne(
        inversedBy: 'affectations'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReglementFournisseur $reglement = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    #[Assert\Positive(
        message: 'Le montant affecté doit être supérieur à zéro.'
    )]
    private ?string $montant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacture(): ?FactureFournisseur
    {
        return $this->facture;
    }

    public function setFacture(
        ?FactureFournisseur $facture
    ): static {
        $this->facture = $facture;

        return $this;
    }

    public function getReglement(): ?ReglementFournisseur
    {
        return $this->reglement;
    }

    public function setReglement(
        ?ReglementFournisseur $reglement
    ): static {
        $this->reglement = $reglement;

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

    public function getMontantFloat(): float
    {
        return round((float) $this->montant, 2);
    }
}
