<?php

namespace App\Entity;

use App\Repository\LigneBudgetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LigneBudgetRepository::class)]
class LigneBudget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        inversedBy: 'lignes'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Budget $budget = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCharge $typeCharge = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\GreaterThan(
        value: 0,
        message: 'Le montant doit être supérieur à zéro.'
    )]
    private ?string $montant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBudget(): ?Budget
    {
        return $this->budget;
    }

    public function setBudget(
        ?Budget $budget
    ): static {

        $this->budget = $budget;

        return $this;
    }

    public function getTypeCharge(): ?TypeCharge
    {
        return $this->typeCharge;
    }

    public function setTypeCharge(
        ?TypeCharge $typeCharge
    ): static {

        $this->typeCharge = $typeCharge;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(
        string $montant
    ): static {

        $this->montant = $montant;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s €',
            $this->typeCharge?->getNom(),
            $this->montant
        );
    }
}
