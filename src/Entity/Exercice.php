<?php

namespace App\Entity;

use App\Entity\Ecriture;
use App\Enum\ExerciceStatut;
use App\Repository\ExerciceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
class Exercice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $nom = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'string', enumType: ExerciceStatut::class)]
    private ExerciceStatut $statut = ExerciceStatut::OUVERT;
    /**
     * @var Collection<int, Ecriture>
     */
    #[ORM\OneToMany(targetEntity: Ecriture::class, mappedBy: 'exercice')]
    private Collection $ecritures;

    /**
     * @var Collection<int, Budget>
     */
    #[ORM\OneToMany(targetEntity: Budget::class, mappedBy: 'exercice')]
    private Collection $budgets;

    #[ORM\ManyToOne(inversedBy: 'exercices',  fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Copropriete $copropriete = null;

    /**
     * @var Collection<int, FactureFournisseur>
     */
    #[ORM\OneToMany(targetEntity: FactureFournisseur::class, mappedBy: 'exercice')]
    private Collection $factureFournisseurs;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'exercice')]
    private Collection $paiements;



    public function __construct()
    {
        $this->ecritures = new ArrayCollection();
        $this->budgets = new ArrayCollection();
        $this->factureFournisseurs = new ArrayCollection();
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function setDateFin(\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getStatut(): ExerciceStatut
    {
        return $this->statut;
    }

    public function setStatut(ExerciceStatut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * @return Collection<int, Ecriture>
     */
    public function getEcritures(): Collection
    {
        return $this->ecritures;
    }

    public function addEcriture(Ecriture $ecriture): static
    {
        if (!$this->ecritures->contains($ecriture)) {
            $this->ecritures->add($ecriture);
            $ecriture->setExercice($this);
        }

        return $this;
    }

    public function removeEcriture(Ecriture $ecriture): static
    {
        if ($this->ecritures->removeElement($ecriture)) {
            // set the owning side to null (unless already changed)
            if ($ecriture->getExercice() === $this) {
                $ecriture->setExercice(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }

    /**
     * @return Collection<int, Budget>
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    public function addBudget(Budget $budget): static
    {
        if (!$this->budgets->contains($budget)) {
            $this->budgets->add($budget);
            $budget->setExercice($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): static
    {
        if ($this->budgets->removeElement($budget)) {
            // set the owning side to null (unless already changed)
            if ($budget->getExercice() === $this) {
                $budget->setExercice(null);
            }
        }

        return $this;
    }

    public function getCopropriete(): ?Copropriete
    {
        return $this->copropriete;
    }

    public function setCopropriete(?Copropriete $copropriete): static
    {
        $this->copropriete = $copropriete;

        return $this;
    }

    /**
     * @return Collection<int, FactureFournisseur>
     */
    public function getFactureFournisseurs(): Collection
    {
        return $this->factureFournisseurs;
    }

    public function addFactureFournisseur(FactureFournisseur $factureFournisseur): static
    {
        if (!$this->factureFournisseurs->contains($factureFournisseur)) {
            $this->factureFournisseurs->add($factureFournisseur);
            $factureFournisseur->setExercice($this);
        }

        return $this;
    }

    public function removeFactureFournisseur(FactureFournisseur $factureFournisseur): static
    {
        if ($this->factureFournisseurs->removeElement($factureFournisseur)) {
            // set the owning side to null (unless already changed)
            if ($factureFournisseur->getExercice() === $this) {
                $factureFournisseur->setExercice(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setExercice($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getExercice() === $this) {
                $paiement->setExercice(null);
            }
        }

        return $this;
    }
}
