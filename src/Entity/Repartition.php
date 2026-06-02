<?php

namespace App\Entity;

use App\Repository\RepartitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepartitionRepository::class)]
class Repartition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'repartitions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lot $lot = null;

    #[ORM\ManyToOne(inversedBy: 'repartitions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ecriture $ecriture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column]
    private ?int $tantiemes = null;

    #[ORM\ManyToOne(inversedBy: 'repartitions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coproprietaire $coproprietaire = null;

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
        if ($this->lot === $lot) {
            return $this;
        }

        $this->lot = $lot;

        if ($lot && !$lot->getRepartitions()->contains($this)) {
            $lot->addRepartition($this);
        }

        return $this;
    }

    public function getEcriture(): ?Ecriture
    {
        return $this->ecriture;
    }

    public function setEcriture(?Ecriture $ecriture): static
    {
        if ($this->ecriture === $ecriture) {
            return $this;
        }

        $this->ecriture = $ecriture;

        if ($ecriture && !$ecriture->getRepartitions()->contains($this)) {
            $ecriture->addRepartition($this);
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

    public function getTantiemes(): ?int
    {
        return $this->tantiemes;
    }

    public function setTantiemes(int $tantiemes): static
    {
        $this->tantiemes = $tantiemes;

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
}
