<?php

namespace App\Entity;

use App\Enum\CompteType;
use App\Repository\CompteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteRepository::class)]
class Compte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $numero;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column(enumType: CompteType::class)]
    private CompteType $type;

    #[ORM\OneToMany(mappedBy: 'compte', targetEntity: Ecriture::class)]
    private Collection $ecritures;

    /**
     * @var Collection<int, TypeCharge>
     */
    #[ORM\OneToMany(targetEntity: TypeCharge::class, mappedBy: 'compte')]
    private Collection $typeCharges;

    /**
     * @var Collection<int, Coproprietaire>
     */
    #[ORM\OneToMany(targetEntity: Coproprietaire::class, mappedBy: 'compte')]
    private Collection $coproprietaires;

    public function __construct()
    {
        $this->ecritures = new ArrayCollection();
        $this->typeCharges = new ArrayCollection();
        $this->coproprietaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getType(): ?CompteType
    {
        return $this->type;
    }

    public function setType(CompteType $type): self
    {
        $this->type = $type;
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
            $ecriture->setCompte($this);
        }
        return $this;
    }

    public function removeEcriture(Ecriture $ecriture): static
    {
        if ($this->ecritures->removeElement($ecriture)) {
            if ($ecriture->getCompte() === $this) {
                $ecriture->setCompte(null);
            }
        }
        return $this;
    }

    public function isCharge(): bool
    {
        return $this->type === CompteType::CHARGE;
    }

    public function __toString(): string
    {
        return $this->numero . ' - ' . $this->libelle;
    }

    /**
     * @return Collection<int, TypeCharge>
     */
    public function getTypeCharges(): Collection
    {
        return $this->typeCharges;
    }

    public function addTypeCharge(TypeCharge $typeCharge): static
    {
        if (!$this->typeCharges->contains($typeCharge)) {
            $this->typeCharges->add($typeCharge);
            $typeCharge->setCompte($this);
        }

        return $this;
    }

    public function removeTypeCharge(TypeCharge $typeCharge): static
    {
        if ($this->typeCharges->removeElement($typeCharge)) {
            // set the owning side to null (unless already changed)
            if ($typeCharge->getCompte() === $this) {
                $typeCharge->setCompte(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Coproprietaire>
     */
    public function getCoproprietaires(): Collection
    {
        return $this->coproprietaires;
    }

    public function addCoproprietaire(Coproprietaire $coproprietaire): static
    {
        if (!$this->coproprietaires->contains($coproprietaire)) {
            $this->coproprietaires->add($coproprietaire);
            $coproprietaire->setCompte($this);
        }

        return $this;
    }

    public function removeCoproprietaire(Coproprietaire $coproprietaire): static
    {
        if ($this->coproprietaires->removeElement($coproprietaire)) {
            // set the owning side to null (unless already changed)
            if ($coproprietaire->getCompte() === $this) {
                $coproprietaire->setCompte(null);
            }
        }

        return $this;
    }

    public function estCompteDeBilan(): bool
    {
        return !in_array(
            $this->type,
            [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ],
            true
        );
    }
}
