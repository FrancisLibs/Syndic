<?php

namespace App\Entity;

use App\Enum\OperationStatut;
use App\Enum\OperationType;
use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $piece = null;

    #[ORM\OneToMany(
        mappedBy: 'operation',
        targetEntity: Ecriture::class,
        cascade: ['persist'],
        orphanRemoval: true,
        fetch: 'EAGER'
    )]
    private Collection $ecritures;

    #[ORM\Column(type: 'string', length: 20, enumType: OperationType::class)]
    private OperationType $type;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TypeCharge $typeCharge = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Lot $lot = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\OneToOne(mappedBy: 'operation', cascade: ['persist', 'remove'])]
    private ?Paiement $paiement = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OperationStatut::class)]
    private OperationStatut $statut;

    #[ORM\OneToOne(mappedBy: 'operation', cascade: ['persist', 'remove'])]
    private ?AppelFond $appelFond = null;

    public function __construct()
    {
        $this->ecritures = new ArrayCollection();
        $this->statut = OperationStatut::VALIDE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    // 3. Modifie le Setter
    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getPiece(): ?string
    {
        return $this->piece;
    }

    public function setPiece(?string $piece): static
    {
        $this->piece = $piece;
        return $this;
    }

    public function getEcritures(): Collection
    {
        return $this->ecritures;
    }

    public function addEcriture(Ecriture $ecriture): static
    {
        if (!$this->ecritures->contains($ecriture)) {
            $this->ecritures->add($ecriture);
            $ecriture->setOperation($this);
        }
        return $this;
    }

    public function removeEcriture(Ecriture $ecriture): static
    {
        if ($this->ecritures->removeElement($ecriture)) {
            if ($ecriture->getOperation() === $this) {
                $ecriture->setOperation(null);
            }
        }
        return $this;
    }

    public function isEquilibree(): bool
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($this->ecritures as $ecriture) {
            $debit += (float) $ecriture->getDebit();
            $credit += (float) $ecriture->getCredit();
        }

        return abs($debit - $credit) < 0.01;
    }

    // Getters et Setters pour Type et Statut
    public function getType(): OperationType
    {
        return $this->type;
    }
    public function setType(OperationType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function valider(): void
    {
        if (!$this->isEquilibree()) {
            throw new \LogicException('Operation non equilibree');
        }

        if (count($this->ecritures) < 2) {
            throw new \LogicException('Operation invalide (au moins 2 lignes)');
        }

        $this->statut = OperationStatut::VALIDE;
    }

    public function __toString(): string
    {
        return $this->libelle;
    }

    public function getTypeCharge(): ?TypeCharge
    {
        return $this->typeCharge;
    }

    public function setTypeCharge(?TypeCharge $typeCharge): static
    {
        $this->typeCharge = $typeCharge;

        return $this;
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

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        // unset the owning side of the relation if necessary
        if ($paiement === null && $this->paiement !== null) {
            $this->paiement->setOperation(null);
        }

        // set the owning side of the relation if necessary
        if ($paiement !== null && $paiement->getOperation() !== $this) {
            $paiement->setOperation($this);
        }

        $this->paiement = $paiement;

        return $this;
    }

    public function getStatut(): OperationStatut
    {
        // ✨ SÉCURITÉ REPRISE DE DONNÉES : Si une vieille ligne en BDD n'a pas de statut,
        // on évite le crash PHP en renvoyant VALIDE par défaut.
        return $this->statut ?? OperationStatut::VALIDE;
    }

    public function setStatut(OperationStatut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getAppelFond(): ?AppelFond
    {
        return $this->appelFond;
    }

    public function setAppelFond(AppelFond $appelFond): static
    {
        // set the owning side of the relation if necessary
        if ($appelFond->getOperation() !== $this) {
            $appelFond->setOperation($this);
        }

        $this->appelFond = $appelFond;

        return $this;
    }
}
