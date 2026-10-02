<?php

namespace App\Entity\Travaux;

use App\Entity\Copropriete;
use App\Entity\AppelFond;
use App\Enum\ModeRepartition;
use App\Repository\Travaux\ChantierRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChantierRepository::class)]
class Chantier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Copropriete $copropriete = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\GreaterThan(
        value: 0,
        message: 'Le montant voté doit être supérieur à zéro.'
    )]
    private ?string $montantVote = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateVote = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroResolution = null;

    #[ORM\Column(enumType: ModeRepartition::class)]
    private ModeRepartition $modeRepartition = ModeRepartition::TANTIEMES;

    #[ORM\Column]
    private bool $cloture = false;

    /**
     * @var Collection<int, AppelFond>
     */
    #[ORM\OneToMany(
        mappedBy: 'chantier',
        targetEntity: AppelFond::class
    )]
    private Collection $appels;

    public function __construct()
    {
        $this->appels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMontantVote(): ?string
    {
        return $this->montantVote;
    }

    public function setMontantVote(string $montantVote): static
    {
        $this->montantVote = $montantVote;

        return $this;
    }

    public function getDateVote(): ?\DateTimeImmutable
    {
        return $this->dateVote;
    }

    public function setDateVote(\DateTimeImmutable $dateVote): static
    {
        $this->dateVote = $dateVote;

        return $this;
    }

    public function getNumeroResolution(): ?string
    {
        return $this->numeroResolution;
    }

    public function setNumeroResolution(?string $numeroResolution): static
    {
        $this->numeroResolution = $numeroResolution;

        return $this;
    }

    public function getModeRepartition(): ModeRepartition
    {
        return $this->modeRepartition;
    }

    public function setModeRepartition(
        ModeRepartition $modeRepartition
    ): static {
        $this->modeRepartition = $modeRepartition;

        return $this;
    }

    public function isCloture(): bool
    {
        return $this->cloture;
    }

    public function setCloture(bool $cloture): static
    {
        $this->cloture = $cloture;

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? 'Chantier #' . $this->id;
    }

    /**
     * @return Collection<int, AppelFond>
     */
    public function getAppels(): Collection
    {
        return $this->appels;
    }

    public function addAppel(AppelFond $appel): static
    {
        if (!$this->appels->contains($appel)) {
            $this->appels->add($appel);
            $appel->setChantier($this);
        }

        return $this;
    }

    public function removeAppel(AppelFond $appel): static
    {
        if ($this->appels->removeElement($appel)) {
            if ($appel->getChantier() === $this) {
                $appel->setChantier(null);
            }
        }

        return $this;
    }
}
