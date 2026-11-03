<?php

namespace App\Entity;

use App\Repository\AssembleeGeneraleRepository;
use App\Enum\AssembleeGeneraleStatut;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssembleeGeneraleRepository::class)]
class AssembleeGenerale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Column(
        type: Types::DATE_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(
        type: Types::TIME_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $heure = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $heureCloture = null;

    #[ORM\Column(
        length: 255,
        nullable: true
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $presidentSeance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $scrutateur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secretaireSeance = null;

    #[ORM\Column(nullable: true)]
    private ?int $tantiemesPresentsRepresentes = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombrePresents = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreRepresentes = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombrePouvoirs = null;

    /**
     * @var Collection<int, Resolution>
     */
    #[ORM\OneToMany(
        targetEntity: Resolution::class,
        mappedBy: 'assembleeGenerale',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['numero' => 'ASC'])]
    private Collection $resolutions;

    /**
     * @var Collection<int, PresenceAssembleeGenerale>
     */
    #[ORM\OneToMany(
        targetEntity: PresenceAssembleeGenerale::class,
        mappedBy: 'assembleeGenerale',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $presences;

    #[ORM\Column(
        enumType: AssembleeGeneraleStatut::class
    )]
    private AssembleeGeneraleStatut $statut = AssembleeGeneraleStatut::PREPARATION;

    public function __construct()
    {
        $this->resolutions = new ArrayCollection();
        $this->presences = new ArrayCollection();
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
        $this->exercice = $exercice;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(
        ?\DateTimeImmutable $date
    ): static {
        $this->date = $date;

        return $this;
    }

    public function getHeure(): ?\DateTimeImmutable
    {
        return $this->heure;
    }

    public function setHeure(
        ?\DateTimeImmutable $heure
    ): static {
        $this->heure = $heure;

        return $this;
    }

    public function getHeureCloture(): ?\DateTimeImmutable
    {
        return $this->heureCloture;
    }

    public function setHeureCloture(?\DateTimeImmutable $heureCloture): static
    {
        $this->heureCloture = $heureCloture;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(
        ?string $lieu
    ): static {
        $this->lieu = $lieu;

        return $this;
    }

    public function getPresidentSeance(): ?string
    {
        return $this->presidentSeance;
    }

    public function setPresidentSeance(?string $presidentSeance): static
    {
        $this->presidentSeance = $presidentSeance;

        return $this;
    }

    public function getScrutateur(): ?string
    {
        return $this->scrutateur;
    }

    public function setScrutateur(?string $scrutateur): static
    {
        $this->scrutateur = $scrutateur;

        return $this;
    }

    public function getSecretaireSeance(): ?string
    {
        return $this->secretaireSeance;
    }

    public function setSecretaireSeance(?string $secretaireSeance): static
    {
        $this->secretaireSeance = $secretaireSeance;

        return $this;
    }

    public function getTantiemesPresentsRepresentes(): ?int
    {
        return $this->tantiemesPresentsRepresentes;
    }

    public function setTantiemesPresentsRepresentes(?int $tantiemesPresentsRepresentes): static
    {
        $this->tantiemesPresentsRepresentes = $tantiemesPresentsRepresentes;

        return $this;
    }

    public function getNombrePresents(): ?int
    {
        return $this->nombrePresents;
    }

    public function setNombrePresents(?int $nombrePresents): static
    {
        $this->nombrePresents = $nombrePresents;

        return $this;
    }

    public function getNombreRepresentes(): ?int
    {
        return $this->nombreRepresentes;
    }

    public function setNombreRepresentes(?int $nombreRepresentes): static
    {
        $this->nombreRepresentes = $nombreRepresentes;

        return $this;
    }

    public function getNombrePouvoirs(): ?int
    {
        return $this->nombrePouvoirs;
    }

    public function setNombrePouvoirs(?int $nombrePouvoirs): static
    {
        $this->nombrePouvoirs = $nombrePouvoirs;

        return $this;
    }

    /**
     * @return Collection<int, Resolution>
     */
    public function getResolutions(): Collection
    {
        return $this->resolutions;
    }

    public function addResolution(Resolution $resolution): static
    {
        if (!$this->resolutions->contains($resolution)) {
            $this->resolutions->add($resolution);
            $resolution->setAssembleeGenerale($this);
        }

        return $this;
    }

    public function removeResolution(Resolution $resolution): static
    {
        if ($this->resolutions->removeElement($resolution)) {
            // set the owning side to null (unless already changed)
            if ($resolution->getAssembleeGenerale() === $this) {
                $resolution->setAssembleeGenerale(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PresenceAssembleeGenerale>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(PresenceAssembleeGenerale $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setAssembleeGenerale($this);
        }

        return $this;
    }

    public function removePresence(PresenceAssembleeGenerale $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            // set the owning side to null (unless already changed)
            if ($presence->getAssembleeGenerale() === $this) {
                $presence->setAssembleeGenerale(null);
            }
        }

        return $this;
    }

    public function getStatut(): AssembleeGeneraleStatut
    {
        return $this->statut;
    }

    public function setStatut(
        AssembleeGeneraleStatut $statut
    ): static {
        $this->statut = $statut;

        return $this;
    }
}
