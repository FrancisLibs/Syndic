<?php

namespace App\Entity;

use App\Repository\LigneAppelFondRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LigneAppelFondRepository::class)]
class LigneAppelFond
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        inversedBy: 'ligneAppelFonds'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?AppelFond $appelFond = null;

    #[ORM\ManyToOne(
        inversedBy: 'ligneAppelFonds'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lot $lot = null;

    #[ORM\ManyToOne(
        inversedBy: 'ligneAppelFonds'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    #[Assert\GreaterThan(
        value: 0,
        message: 'Le montant doit être supérieur à zéro.'
    )]
    private ?string $montant = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 5,
        scale: 2,
        nullable: true
    )]
    private ?string $pourcentage = null;

    #[ORM\Column(
        type: 'decimal',
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private string $montantRegle = '0.00';

    #[ORM\Column(options: ['default' => false])]
    private bool $soldee = false;

    /**
     * @var Collection<int, AffectationPaiement>
     */
    #[ORM\OneToMany(
        mappedBy: 'ligneAppel',
        targetEntity: AffectationPaiement::class,
        cascade: ['persist', 'remove'],
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

    public function getAppelFond(): ?AppelFond
    {
        return $this->appelFond;
    }

    public function setAppelFond(
        ?AppelFond $appelFond
    ): static {

        $this->appelFond =
            $appelFond;

        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(
        ?Lot $lot
    ): static {

        $this->lot = $lot;

        return $this;
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(
        ?Coproprietaire $coproprietaire
    ): static {

        $this->coproprietaire =
            $coproprietaire;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(
        string $montant
    ): static {

        $this->montant =
            $montant;

        return $this;
    }

    public function getPourcentage(): ?string
    {
        return $this->pourcentage;
    }

    public function setPourcentage(
        ?string $pourcentage
    ): static {

        $this->pourcentage =
            $pourcentage;

        return $this;
    }

    public function getMontantRegle(): ?string
    {
        return $this->montantRegle;
    }

    public function setMontantRegle(?string $montantRegle): static
    {
        $this->montantRegle = $montantRegle;

        return $this;
    }

    public function isSoldee(): ?bool
    {
        return $this->soldee;
    }

    public function setSoldee(bool $soldee): static
    {
        $this->soldee = $soldee;

        return $this;
    }

    public function getResteAPayer(): float
    {
        return
            (float) $this->montant
            - (float) $this->montantRegle;
    }

    /**
     * @return Collection<int, AffectationPaiement>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(AffectationPaiement $affectation): static
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations->add($affectation);
            $affectation->setLigneAppel($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s €',
            $this->coproprietaire?->getNom(),
            $this->montant
        );
    }
}
