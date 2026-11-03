<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ReglementFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ReglementFournisseurRepository::class)]
class ReglementFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateReglement = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    private ?string $montant = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Operation $operation = null;

    /**
     * @var Collection<int, AffectationReglementFournisseur>
     */
    #[ORM\OneToMany(
        mappedBy: 'reglement',
        targetEntity: AffectationReglementFournisseur::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $affectations;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReglement(): ?\DateTimeImmutable
    {
        return $this->dateReglement;
    }

    public function setDateReglement(
        \DateTimeImmutable $dateReglement
    ): static {
        $this->dateReglement = $dateReglement;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(
        ?Fournisseur $fournisseur
    ): static {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(
        ?Exercice $exercice
    ): static {
        $this->exercice = $exercice;

        return $this;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(
        ?Operation $operation
    ): static {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return Collection<int, AffectationReglementFournisseur>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(
        AffectationReglementFournisseur $affectation
    ): static {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations->add($affectation);
            $affectation->setReglement($this);
        }

        return $this;
    }

    public function removeAffectation(
        AffectationReglementFournisseur $affectation
    ): static {
        if ($this->affectations->removeElement($affectation)) {
            if ($affectation->getReglement() === $this) {
                $affectation->setReglement(null);
            }
        }

        return $this;
    }

    public function getMontantAffecte(): float
    {
        $total = 0.0;

        foreach ($this->affectations as $affectation) {
            $total += $affectation->getMontantFloat();
        }

        return round($total, 2);
    }

    public function getMontantNonAffecte(): float
    {
        return round(
            (float) $this->montant - $this->getMontantAffecte(),
            2
        );
    }
}
