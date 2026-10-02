<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Repository\ResolutionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\MajoriteResolution;

#[ORM\Entity(repositoryClass: ResolutionRepository::class)]
class Resolution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resolutions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AssembleeGenerale $assembleeGenerale = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $texte = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decision = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $amendeeEnSeance = false;

    #[ORM\Column(
        enumType: MajoriteResolution::class,
        nullable: true
    )]
    private ?MajoriteResolution $majorite = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesPour = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesContre = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesAbstention = null;

    #[ORM\Column(nullable: true)]
    private ?bool $adoptee = null;

    #[ORM\Column]
    private bool $secondVoteNecessaire = false;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesPourSecondTour = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesContreSecondTour = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesAbstentionSecondTour = null;

    /**
     * @var Collection<int, VoteResolution>
     */
    #[ORM\OneToMany(targetEntity: VoteResolution::class, mappedBy: 'resolution', orphanRemoval: true)]
    private Collection $votes;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssembleeGenerale(): ?AssembleeGenerale
    {
        return $this->assembleeGenerale;
    }

    public function setAssembleeGenerale(?AssembleeGenerale $assembleeGenerale): static
    {
        $this->assembleeGenerale = $assembleeGenerale;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): static
    {
        $this->decision = $decision;

        return $this;
    }

    public function isAmendeeEnSeance(): bool
    {
        return $this->amendeeEnSeance;
    }

    public function setAmendeeEnSeance(bool $amendeeEnSeance): static
    {
        $this->amendeeEnSeance = $amendeeEnSeance;

        return $this;
    }

    public function getMajorite(): ?MajoriteResolution
    {
        return $this->majorite;
    }

    public function setMajorite(?MajoriteResolution $majorite): static
    {
        $this->majorite = $majorite;

        return $this;
    }

    public function getTantiemesPour(): ?int
    {
        return $this->tantiemesPour;
    }

    public function setTantiemesPour(?int $tantiemesPour): static
    {
        $this->tantiemesPour = $tantiemesPour;

        return $this;
    }

    public function getTantiemesContre(): ?int
    {
        return $this->tantiemesContre;
    }

    public function setTantiemesContre(?int $tantiemesContre): static
    {
        $this->tantiemesContre = $tantiemesContre;

        return $this;
    }

    public function getTantiemesAbstention(): ?int
    {
        return $this->tantiemesAbstention;
    }

    public function setTantiemesAbstention(?int $tantiemesAbstention): static
    {
        $this->tantiemesAbstention = $tantiemesAbstention;

        return $this;
    }

    public function isAdoptee(): ?bool
    {
        return $this->adoptee;
    }

    public function setAdoptee(?bool $adoptee): static
    {
        $this->adoptee = $adoptee;

        return $this;
    }

    /**
     * @return Collection<int, VoteResolution>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(VoteResolution $vote): static
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setResolution($this);
        }

        return $this;
    }

    public function removeVote(VoteResolution $vote): static
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getResolution() === $this) {
                $vote->setResolution(null);
            }
        }

        return $this;
    }

    public function isSecondVoteNecessaire(): bool
    {
        return $this->secondVoteNecessaire;
    }

    public function setSecondVoteNecessaire(
        bool $secondVoteNecessaire
    ): static {
        $this->secondVoteNecessaire = $secondVoteNecessaire;

        return $this;
    }

    public function getTantiemesPourSecondTour(): ?int
    {
        return $this->tantiemesPourSecondTour;
    }

    public function setTantiemesPourSecondTour(?int $tantiemes): static
    {
        $this->tantiemesPourSecondTour = $tantiemes;

        return $this;
    }

    public function getTantiemesContreSecondTour(): ?int
    {
        return $this->tantiemesContreSecondTour;
    }

    public function setTantiemesContreSecondTour(?int $tantiemes): static
    {
        $this->tantiemesContreSecondTour = $tantiemes;

        return $this;
    }

    public function getTantiemesAbstentionSecondTour(): ?int
    {
        return $this->tantiemesAbstentionSecondTour;
    }

    public function setTantiemesAbstentionSecondTour(?int $tantiemes): static
    {
        $this->tantiemesAbstentionSecondTour = $tantiemes;

        return $this;
    }

    public function hasSecondVoteEnregistre(): bool
    {
        return
            $this->tantiemesPourSecondTour !== null
            || $this->tantiemesContreSecondTour !== null
            || $this->tantiemesAbstentionSecondTour !== null;
    }
}
