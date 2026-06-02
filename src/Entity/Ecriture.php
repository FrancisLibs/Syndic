<?php

namespace App\Entity;

use App\Repository\EcritureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EcritureRepository::class)]
class Ecriture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ecritures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compte $compte = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ["default" => "0.00"])]
    private string $debit = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ["default" => "0.00"])]
    private string $credit = '0.00';

    #[ORM\ManyToOne(targetEntity: Operation::class, inversedBy: 'ecritures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Operation $operation = null;

    #[ORM\ManyToOne(targetEntity: Exercice::class, inversedBy: 'ecritures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    /**
     * @var Collection<int, Repartition>
     */
    #[
        ORM\OneToMany(
            targetEntity: Repartition::class,
            mappedBy: 'ecriture',
            cascade: [
                'persist'
            ]
        )
    ]
    private Collection $repartitions;

    #[ORM\ManyToOne(inversedBy: 'ecritures')]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    public function __construct()
    {
        $this->repartitions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDebit(): string
    {
        return $this->debit;
    }

    public function setDebit(string $debit): static
    {
        $debit = trim($debit);

        if ($debit === '' || $debit === null) {
            $debit = '0.00';
        }

        if (bccomp($debit, '0', 2) === -1) {
            throw new \LogicException('Le débit ne peut pas être négatif');
        }

        if (bccomp($debit, '0', 2) === 1 && bccomp($this->credit, '0', 2) === 1) {
            throw new \LogicException('Une écriture ne peut pas avoir débit ET crédit');
        }

        $this->debit = $debit;
        return $this;
    }

    public function getCredit(): string
    {
        return $this->credit;
    }

    public function setCredit(string $credit): static
    {
        $credit = trim($credit);

        if ($credit === '') {
            $credit = '0.00';
        }

        if (bccomp($credit, '0', 2) === -1) {
            throw new \LogicException('Le crédit ne peut pas être négatif');
        }

        if (bccomp($credit, '0', 2) === 1 && bccomp($this->debit, '0', 2) === 1) {
            throw new \LogicException('Une écriture ne peut pas avoir débit ET crédit');
        }

        $this->credit = $credit;
        return $this;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): static
    {
        if ($this->operation === $operation) {
            return $this;
        }

        $this->operation = $operation;

        if ($operation && !$operation->getEcritures()->contains($this)) {
            $operation->addEcriture($this);
        }

        return $this;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): static
    {
        if ($this->exercice === $exercice) {
            return $this;
        }

        $this->exercice = $exercice;

        if ($exercice && !$exercice->getEcritures()->contains($this)) {
            $exercice->addEcriture($this);
        }

        return $this;
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
            $repartition->setEcriture($this);
        }

        return $this;
    }

    public function removeRepartition(Repartition $repartition): static
    {
        if ($this->repartitions->removeElement($repartition)) {
            // set the owning side to null (unless already changed)
            if ($repartition->getEcriture() === $this) {
                $repartition->setEcriture(null);
            }
        }

        return $this;
    }

    public function isComptableValid(): bool
    {
        return (
            bccomp($this->debit, '0', 2) === 1 && bccomp($this->credit, '0', 2) === 0
        ) || (
            bccomp($this->credit, '0', 2) === 1 && bccomp($this->debit, '0', 2) === 0
        );
    }

    public function validate(): void
    {
        if (!$this->isComptableValid()) {
            throw new \LogicException('Écriture invalide : débit/crédit incorrect');
        }
    }

    public function getMontant(): string
    {
        return bccomp($this->debit, '0', 2) === 1
            ? $this->debit
            : $this->credit;
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(?Coproprietaire $coproprietaire): static
    {
        $this->coproprietaire = $coproprietaire;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }
}
