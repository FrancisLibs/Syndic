<?php

namespace App\Entity;

use App\Repository\ApprobationComptesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApprobationComptesRepository::class)]
class ApprobationComptes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(
        inversedBy: 'approbationComptes'
    )]
    #[ORM\JoinColumn(
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Exercice $exercice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAssembleeGenerale = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numeroResolution = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Operation $operation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(
        Exercice $exercice
    ): static {
        $this->exercice = $exercice;

        return $this;
    }

    public function getDateAssembleeGenerale(): ?\DateTimeImmutable
    {
        return $this->dateAssembleeGenerale;
    }

    public function setDateAssembleeGenerale(
        \DateTimeImmutable $dateAssembleeGenerale
    ): static {
        $this->dateAssembleeGenerale =
            $dateAssembleeGenerale;

        return $this;
    }

    public function getNumeroResolution(): ?string
    {
        return $this->numeroResolution;
    }

    public function setNumeroResolution(
        ?string $numeroResolution
    ): static {
        $this->numeroResolution =
            $numeroResolution;

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
}
