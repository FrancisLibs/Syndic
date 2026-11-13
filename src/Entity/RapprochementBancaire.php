<?php

namespace App\Entity;

use App\Repository\RapprochementBancaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RapprochementBancaireRepository::class)]
#[ORM\Table(name: 'rapprochement_bancaire')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_RAPPROCHEMENT_ECRITURE',
    columns: ['ecriture_id']
)]
class RapprochementBancaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ecriture::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ecriture $ecriture = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateRapprochement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $referenceReleve = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEcriture(): ?Ecriture
    {
        return $this->ecriture;
    }

    public function setEcriture(Ecriture $ecriture): static
    {
        $this->ecriture = $ecriture;

        return $this;
    }

    public function getDateRapprochement(): ?\DateTimeImmutable
    {
        return $this->dateRapprochement;
    }

    public function setDateRapprochement(
        \DateTimeImmutable $dateRapprochement
    ): static {
        $this->dateRapprochement = $dateRapprochement;

        return $this;
    }

    public function getReferenceReleve(): ?string
    {
        return $this->referenceReleve;
    }

    public function setReferenceReleve(?string $referenceReleve): static
    {
        $this->referenceReleve = $referenceReleve;

        return $this;
    }
}
