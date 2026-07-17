<?php

namespace App\Entity;

use App\Repository\CompteurEauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteurEauRepository::class)]
class CompteurEau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $general = false;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\OneToOne(inversedBy: 'compteurEau')]
    #[ORM\JoinColumn(nullable: true, unique: true)]
    private ?Lot $lot = null;

    #[ORM\Column(nullable: true)]
    private ?int $indexInitial = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;


    #[ORM\OneToMany(
        mappedBy: 'compteur',
        targetEntity: ReleveCompteur::class,
        orphanRemoval: true
    )]
    private Collection $releves;

    public function __construct()
    {
        $this->releves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isGeneral(): bool
    {
        return $this->general;
    }

    public function setGeneral(bool $general): static
    {
        $this->general = $general;

        if ($general) {
            $this->setLot(null);
        }

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): static
    {
        $this->lot = $lot;
        if (
            $lot !== null
            && $lot->getCompteurEau() !== $this
        ) {
            $lot->setCompteurEau($this);
        }

        return $this;
    }

    public function getIndexInitial(): ?int
    {
        return $this->indexInitial;
    }

    public function setIndexInitial(?int $indexInitial): static
    {
        $this->indexInitial = $indexInitial;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, ReleveCompteur>
     */
    public function getReleves(): Collection
    {
        return $this->releves;
    }

    public function addReleve(
        ReleveCompteur $releve
    ): static {
        if (!$this->releves->contains($releve)) {
            $this->releves->add($releve);
            $releve->setCompteur($this);
        }

        return $this;
    }

    public function removeReleve(
        ReleveCompteur $releve
    ): static {
        if ($this->releves->removeElement($releve)) {
            if ($releve->getCompteur() === $this) {
                $releve->setCompteur(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->numero ?? 'Compteur sans numéro';
    }
}
