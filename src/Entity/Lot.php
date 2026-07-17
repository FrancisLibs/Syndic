<?php

namespace App\Entity;

use App\Entity\Coproprietaire;
use App\Repository\LotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LotRepository::class)]
class Lot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $reference = null;

    #[ORM\Column(length: 100)]
    private ?string $designation = null;

    #[ORM\Column]
    private ?int $tantiemes = null;

    #[ORM\ManyToOne(inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Copropriete $copropriete = null;

    #[
        ORM\OneToMany(
            mappedBy: 'lot',
            targetEntity: LotCoproprietaire::class,
            cascade: ['persist', 'remove']
        )
    ]
    private Collection $lotCoproprietaires;

    /**
     * @var Collection<int, Repartition>
     */
    #[ORM\OneToMany(targetEntity: Repartition::class, mappedBy: 'lot')]
    private Collection $repartitions;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'lot')]
    private Collection $operations;

    /**
     * @var Collection<int, LigneAppelFond>
     */
    #[ORM\OneToMany(targetEntity: LigneAppelFond::class, mappedBy: 'lot')]
    private Collection $ligneAppelFonds;

    #[ORM\OneToOne(
        mappedBy: 'lot',
        targetEntity: CompteurEau::class
    )]
    private ?CompteurEau $compteurEau = null;

    public function __construct()
    {
        $this->repartitions = new ArrayCollection();
        $this->lotCoproprietaires = new ArrayCollection();
        $this->operations = new ArrayCollection();
        $this->ligneAppelFonds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;

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

    public function getCopropriete(): ?Copropriete
    {
        return $this->copropriete;
    }

    public function setCopropriete(?Copropriete $copropriete): static
    {
        $this->copropriete = $copropriete;

        return $this;
    }

    public function getCoproprietaireActuel(
        ?\DateTimeInterface $date = null
    ): ?Coproprietaire {

        $copros = $this->getCoproprietairesActifs($date);

        if (count($copros) === 1) {
            return $copros[0];
        }

        return null;
    }

    public function getCoproprietairesActifs(
        ?\DateTimeInterface $date = null
    ): array {

        $date ??= new \DateTime();

        $copros = [];

        foreach ($this->lotCoproprietaires as $relation) {

            $dateDebut = $relation->getDateDebut();
            $dateFin = $relation->getDateFin();

            $actif = $dateDebut <= $date
                && (
                    $dateFin === null
                    || $dateFin >= $date
                );

            if ($actif) {
                $copros[] = $relation->getCoproprietaire();
            }
        }

        return $copros;
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
            $repartition->setLot($this);
        }

        return $this;
    }

    public function removeRepartition(Repartition $repartition): static
    {
        if ($this->repartitions->removeElement($repartition)) {
            // set the owning side to null (unless already changed)
            if ($repartition->getLot() === $this) {
                $repartition->setLot(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LotCoproprietaire>
     */
    public function getLotCoproprietaires(): Collection
    {
        return $this->lotCoproprietaires;
    }

    public function addLotCoproprietaire(LotCoproprietaire $rel): static
    {
        if (!$this->lotCoproprietaires->contains($rel)) {
            $this->lotCoproprietaires->add($rel);
            $rel->setLot($this);
        }

        return $this;
    }

    public function removeLotCoproprietaire(LotCoproprietaire $lotCoproprietaire): static
    {
        if ($this->lotCoproprietaires->removeElement($lotCoproprietaire)) {
            // set the owning side to null (unless already changed)
            if ($lotCoproprietaire->getLot() === $this) {
                $lotCoproprietaire->setLot(null);
            }
        }

        return $this;
    }

    public function getRelationsActives(?\DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable();

        return array_values(
            array_filter(
                $this->lotCoproprietaires->toArray(),
                fn(LotCoproprietaire $rel) =>
                $rel->getDateDebut() <= $date &&
                    (
                        $rel->getDateFin() === null ||
                        $rel->getDateFin() >= $date
                    )
            )
        );
    }

    public function hasMultipleOwners(?\DateTimeInterface $date = null): bool
    {
        return count($this->getCoproprietairesActifs($date)) > 1;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setLot($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getLot() === $this) {
                $operation->setLot(null);
            }
        }

        return $this;
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
            $ligneAppelFond->setLot($this);
        }

        return $this;
    }

    public function removeLigneAppelFond(LigneAppelFond $ligneAppelFond): static
    {
        if ($this->ligneAppelFonds->removeElement($ligneAppelFond)) {
            // set the owning side to null (unless already changed)
            if ($ligneAppelFond->getLot() === $this) {
                $ligneAppelFond->setLot(null);
            }
        }
        return $this;
    }

    public function getCompteurEau(): ?CompteurEau
    {
        return $this->compteurEau;
    }

    public function setCompteurEau(
        ?CompteurEau $compteurEau
    ): static {
        // unset the owning side of the relation if necessary
        if ($compteurEau === null && $this->compteurEau !== null) {
            $this->compteurEau->setLot(null);
        }
        // set the owning side of the relation if necessary
        if ($compteurEau !== null && $compteurEau->getLot() !== $this) {
            $compteurEau->setLot($this);
        }
        $this->compteurEau = $compteurEau;
        return $this;
    }
}
