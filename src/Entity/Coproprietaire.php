<?php

namespace App\Entity;

use App\Repository\CoproprietaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoproprietaireRepository::class)]
class Coproprietaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    /**
     * @var Collection<int, LotCoproprietaire>
     */
    #[ORM\OneToMany(targetEntity: LotCoproprietaire::class, mappedBy: 'coproprietaire')]
    private Collection $lotCoproprietaires;

    /**
     * @var Collection<int, Repartition>
     */
    #[ORM\OneToMany(targetEntity: Repartition::class, mappedBy: 'coproprietaire')]
    private Collection $repartitions;

    /**
     * @var Collection<int, Ecriture>
     */
    #[ORM\OneToMany(targetEntity: Ecriture::class, mappedBy: 'coproprietaire')]
    private Collection $ecritures;

    /**
     * @var Collection<int, LigneAppelFond>
     */
    #[ORM\OneToMany(targetEntity: LigneAppelFond::class, mappedBy: 'coproprietaire')]
    private Collection $ligneAppelFonds;

    #[ORM\ManyToOne(inversedBy: 'coproprietaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compte $compte = null;

    #[ORM\OneToMany(
        mappedBy: 'coproprietaire',
        targetEntity: Paiement::class
    )]
    private Collection $paiements;

    public function __construct()
    {
        $this->lotCoproprietaires = new ArrayCollection();
        $this->repartitions = new ArrayCollection();
        $this->ecritures = new ArrayCollection();
        $this->ligneAppelFonds = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection<int, LotCoproprietaire>
     */
    public function getLotCoproprietaires(): Collection
    {
        return $this->lotCoproprietaires;
    }

    public function addLotCoproprietaire(LotCoproprietaire $lotCoproprietaire): static
    {
        if (!$this->lotCoproprietaires->contains($lotCoproprietaire)) {
            $this->lotCoproprietaires->add($lotCoproprietaire);
            $lotCoproprietaire->setCoproprietaire($this);
        }

        return $this;
    }

    public function removeLotCoproprietaire(LotCoproprietaire $lotCoproprietaire): static
    {
        if ($this->lotCoproprietaires->removeElement($lotCoproprietaire)) {
            // set the owning side to null (unless already changed)
            if ($lotCoproprietaire->getCoproprietaire() === $this) {
                $lotCoproprietaire->setCoproprietaire(null);
            }
        }

        return $this;
    }

    public function getLotsActifs(?\DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable();

        return array_map(
            fn(LotCoproprietaire $rel) => $rel->getLot(),
            array_filter(
                $this->lotCoproprietaires->toArray(),
                fn(LotCoproprietaire $rel) =>
                $rel->getDateDebut() <= $date &&
                    ($rel->getDateFin() === null || $rel->getDateFin() >= $date)
            )
        );
    }

    public function getLotsActifsCount(): int
    {
        return count($this->getLotsActifs());
    }

    /**
     * @return Collection<int, Repartition>
     */
    public function getRepartitions(): Collection
    {
        return $this->repartitions;
    }

    public function addRepartition(Repartition $repartition): static
    {
        if (!$this->repartitions->contains($repartition)) {
            $this->repartitions->add($repartition);
            $repartition->setCoproprietaire($this);
        }

        return $this;
    }

    public function removeRepartition(Repartition $repartition): static
    {
        if ($this->repartitions->removeElement($repartition)) {
            // set the owning side to null (unless already changed)
            if ($repartition->getCoproprietaire() === $this) {
                $repartition->setCoproprietaire(null);
            }
        }

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
            $ecriture->setCoproprietaire($this);
        }

        return $this;
    }

    public function removeEcriture(Ecriture $ecriture): static
    {
        if ($this->ecritures->removeElement($ecriture)) {
            // set the owning side to null (unless already changed)
            if ($ecriture->getCoproprietaire() === $this) {
                $ecriture->setCoproprietaire(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // On affiche le nom et prénom du propriétaire dans les listes de choix
        return trim(($this->prenom ?? '') . ' ' . $this->nom) ?: 'Anonyme';
    }

    /**
     * @return Collection<int, LigneAppelFond>
     */
    public function getLigneAppelFonds(): Collection
    {
        return $this->ligneAppelFonds;
    }

    public function addLigneAppelFond(LigneAppelFond $ligneAppelFond): static
    {
        if (!$this->ligneAppelFonds->contains($ligneAppelFond)) {
            $this->ligneAppelFonds->add($ligneAppelFond);
            $ligneAppelFond->setCoproprietaire($this);
        }

        return $this;
    }

    public function removeLigneAppelFond(LigneAppelFond $ligneAppelFond): static
    {
        if ($this->ligneAppelFonds->removeElement($ligneAppelFond)) {
            // set the owning side to null (unless already changed)
            if ($ligneAppelFond->getCoproprietaire() === $this) {
                $ligneAppelFond->setCoproprietaire(null);
            }
        }

        return $this;
    }

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(?Compte $compte): static
    {
        $this->compte = $compte;

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
            $paiement->setCoproprietaire($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getCoproprietaire() === $this) {
                $paiement->setCoproprietaire(null);
            }
        }

        return $this;
    }
}
