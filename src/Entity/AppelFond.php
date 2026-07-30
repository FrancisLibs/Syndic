<?php

namespace App\Entity;

use App\Repository\AppelFondRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppelFondRepository::class)]
class AppelFond
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Budget $budget = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateAppel = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateReglement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantTotal = null;

    #[ORM\OneToMany(
        mappedBy: 'appelFond',
        targetEntity: LigneAppelFond::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $ligneAppelFonds;

    #[ORM\OneToOne(inversedBy: 'appelFond', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Operation $operation = null;

    #[ORM\Column(length: 20)]
    private ?string $numero = null;

    public function __construct()
    {
        $this->ligneAppelFonds =
            new ArrayCollection();
    }

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

    public function getDateAppel(): ?\DateTimeImmutable
    {
        return $this->dateAppel;
    }

    public function setDateAppel(
        \DateTimeImmutable $dateAppel
    ): static {

        $this->dateAppel = $dateAppel;

        return $this;
    }

    public function getDateReglement(): ?\DateTimeImmutable
    {
        return $this->dateReglement;
    }

    public function setDateReglement(
        ?\DateTimeImmutable $dateReglement
    ): static {

        $this->dateReglement =
            $dateReglement;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(
        ?string $libelle
    ): static {

        $this->libelle = $libelle;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(
        string $montantTotal
    ): static {

        $this->montantTotal =
            $montantTotal;

        return $this;
    }

    /**
     * @return Collection<int, LigneAppelFond>
     */
    public function getLigneAppelFonds(): Collection
    {
        return $this->ligneAppelFonds;
    }

    public function addLigneAppelFond(
        LigneAppelFond $ligne
    ): static {

        if (!$this->ligneAppelFonds->contains($ligne)) {

            $this->ligneAppelFonds
                ->add($ligne);

            $ligne->setAppelFond($this);
        }

        return $this;
    }

    public function removeLigneAppelFond(
        LigneAppelFond $ligne
    ): static {

        if ($this->ligneAppelFonds->removeElement($ligne)) {

            if ($ligne->getAppelFond() === $this) {

                $ligne->setAppelFond(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle
            ?? 'Nouvel appel de fonds';
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): static
    {
        $this->operation = $operation;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }
}
