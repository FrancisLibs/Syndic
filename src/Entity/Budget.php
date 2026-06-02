<?php

namespace App\Entity;

use App\Repository\BudgetRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]

#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'budget_unique_exercice',
            columns: ['copropriete_id', 'exercice_id']
        )
    ]
)]

#[UniqueEntity(
    fields: ['exercice', 'copropriete'],
    message: 'Un budget existe déjà pour cet exercice et cette copropriété.'
)]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'budgets', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\ManyToOne(inversedBy: 'budgets', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Copropriete $copropriete = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, LigneBudget>
     */
    #[ORM\OneToMany(
        mappedBy: 'budget',
        targetEntity: LigneBudget::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $lignes;

    /**
     * @var Collection<int, AppelFond>
     */
    #[ORM\OneToMany(
        mappedBy: 'budget',
        targetEntity: AppelFond::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $appels;

    #[ORM\Column]
    private ?bool $verrouille = null;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->appels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): static
    {
        if (
            $exercice
            && $this->copropriete
            && $exercice->getCopropriete() !== $this->copropriete
        ) {
            throw new \LogicException(
                'Exercice et copropriété incohérents'
            );
        }

        $this->exercice = $exercice;

        return $this;
    }

    public function getCopropriete(): ?Copropriete
    {
        return $this->copropriete;
    }

    public function setCopropriete(
        ?Copropriete $copropriete
    ): static {

        if (
            $copropriete
            && $this->exercice
            && $this->exercice->getCopropriete() !== $copropriete
        ) {
            throw new \LogicException(
                'Exercice et copropriété incohérents'
            );
        }

        $this->copropriete = $copropriete;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, LigneBudget>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(
        LigneBudget $ligne
    ): static {

        if (!$this->lignes->contains($ligne)) {

            $this->lignes->add($ligne);

            $ligne->setBudget($this);
        }

        return $this;
    }

    public function removeLigne(
        LigneBudget $ligne
    ): static {

        if ($this->lignes->removeElement($ligne)) {

            if ($ligne->getBudget() === $this) {

                $ligne->setBudget(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AppelFond>
     */
    public function getAppels(): Collection
    {
        return $this->appels;
    }

    public function addAppel(
        AppelFond $appel
    ): static {

        if (!$this->appels->contains($appel)) {

            $this->appels->add($appel);

            $appel->setBudget($this);
        }

        return $this;
    }

    public function removeAppel(
        AppelFond $appel
    ): static {

        if ($this->appels->removeElement($appel)) {

            if ($appel->getBudget() === $this) {

                $appel->setBudget(null);
            }
        }

        return $this;
    }

    public function getTotal(): float
    {
        return array_sum(

            $this->lignes
                ->map(
                    fn(LigneBudget $ligne)
                    => (float) $ligne->getMontant()
                )
                ->toArray()
        );
    }

    public function __toString(): string
    {
        return $this->libelle
            ?? 'Budget #' . $this->id;
    }

    public function isVerrouille(): ?bool
    {
        return $this->verrouille;
    }

    public function setVerrouille(bool $verrouille): static
    {
        $this->verrouille = $verrouille;

        return $this;
    }
}
