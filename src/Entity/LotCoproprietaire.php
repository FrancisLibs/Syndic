<?php

namespace App\Entity;

use App\Repository\LotCoproprietaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LotCoproprietaireRepository::class)]
class LotCoproprietaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lotCoproprietaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lot $lot = null;

    #[ORM\ManyToOne(inversedBy: 'lotCoproprietaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $pourcentage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): static
    {
        $this->lot = $lot;

        return $this;
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(?Coproprietaire $coproprietaire): static
    {
        $this->coproprietaire = $coproprietaire;

        return $this;
    }

    public function getPourcentage(): ?string
    {
        return $this->pourcentage;
    }

    public function setPourcentage(string $pourcentage): static
    {
        $this->pourcentage = $pourcentage;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }
}
