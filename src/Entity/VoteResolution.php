<?php

namespace App\Entity;

use App\Repository\VoteResolutionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\VoteChoix;

#[ORM\Entity(repositoryClass: VoteResolutionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE_TOUR',
    columns: ['resolution_id', 'coproprietaire_id', 'tour']
)]
class VoteResolution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resolution $resolution = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column]
    private ?int $nombreVoix = null;

    #[ORM\Column(enumType: VoteChoix::class, nullable: true)]
    private ?VoteChoix $choix = null;
    
    #[ORM\Column]
    private int $tour = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResolution(): ?Resolution
    {
        return $this->resolution;
    }

    public function setResolution(?Resolution $resolution): static
    {
        $this->resolution = $resolution;

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

    public function getChoix(): ?VoteChoix
    {
        return $this->choix;
    }

    public function setChoix(?VoteChoix $choix): static
    {
        $this->choix = $choix;

        return $this;
    }

    public function getTour(): int
    {
        return $this->tour;
    }

    public function setTour(int $tour): static
    {
        $this->tour = $tour;

        return $this;
    }
}
