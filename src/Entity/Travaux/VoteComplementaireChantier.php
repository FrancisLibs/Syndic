<?php

namespace App\Entity\Travaux;

use App\Entity\AppelFond;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class VoteComplementaireChantier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'votesComplementaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chantier $chantier = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    #[Assert\GreaterThan(
        value: 0,
        message: 'Le montant complémentaire doit être supérieur à zéro.'
    )]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateVote = null;

    #[ORM\Column(length: 100)]
    private ?string $numeroResolution = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?AppelFond $appelFond = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): static
    {
        $this->chantier = $chantier;

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

    public function getDateVote(): ?\DateTimeImmutable
    {
        return $this->dateVote;
    }

    public function setDateVote(
        \DateTimeImmutable $dateVote
    ): static {
        $this->dateVote = $dateVote;

        return $this;
    }

    public function getNumeroResolution(): ?string
    {
        return $this->numeroResolution;
    }

    public function setNumeroResolution(
        string $numeroResolution
    ): static {
        $this->numeroResolution = $numeroResolution;

        return $this;
    }

    public function getAppelFond(): ?AppelFond
    {
        return $this->appelFond;
    }

    public function setAppelFond(?AppelFond $appelFond): static
    {
        $this->appelFond = $appelFond;

        return $this;
    }
}
