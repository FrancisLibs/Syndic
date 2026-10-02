<?php

namespace App\Entity;

use App\Enum\PresenceStatut;
use App\Repository\PresenceAssembleeGeneraleRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: PresenceAssembleeGeneraleRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_PRESENCE_AG_COPROPRIETAIRE',
    columns: ['assemblee_generale_id', 'coproprietaire_id']
)]
class PresenceAssembleeGenerale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AssembleeGenerale $assembleeGenerale = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column]
    private ?int $nombreVoix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mandataire = null;

    #[ORM\Column(enumType: PresenceStatut::class)]
    private ?PresenceStatut $statut = null;

    #[ORM\Column(
        type: Types::DATE_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $dateReceptionVoteCorrespondance = null;

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

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(?Coproprietaire $coproprietaire): static
    {
        $this->coproprietaire = $coproprietaire;

        return $this;
    }

    public function getNombreVoix(): ?int
    {
        return $this->nombreVoix;
    }

    public function setNombreVoix(int $nombreVoix): static
    {
        $this->nombreVoix = $nombreVoix;

        return $this;
    }

    public function getMandataire(): ?string
    {
        return $this->mandataire;
    }

    public function setMandataire(?string $mandataire): static
    {
        $this->mandataire = $mandataire;

        return $this;
    }

    public function getStatut(): ?PresenceStatut
    {
        return $this->statut;
    }

    public function setStatut(PresenceStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateReceptionVoteCorrespondance(): ?\DateTimeImmutable
    {
        return $this->dateReceptionVoteCorrespondance;
    }

    public function setDateReceptionVoteCorrespondance(
        ?\DateTimeImmutable $dateReceptionVoteCorrespondance
    ): static {
        $this->dateReceptionVoteCorrespondance =
            $dateReceptionVoteCorrespondance;

        return $this;
    }
}
