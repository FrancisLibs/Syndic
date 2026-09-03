<?php

namespace App\Entity\Imports;

use App\Entity\Exercice;
use App\Entity\Fournisseur;
use App\Entity\ReglementFournisseur;
use App\Enum\ImportStatut;
use App\Repository\Imports\ImportReglementFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: ImportReglementFournisseurRepository::class
)]
class ImportReglementFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateReglement = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    private ?string $montant = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(enumType: ImportStatut::class)]
    private ImportStatut $statut = ImportStatut::EN_ATTENTE;

    #[ORM\Column(
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $erreur = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?ReglementFournisseur $reglementCree = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(
        ?Exercice $exercice
    ): static {
        $this->exercice = $exercice;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(
        ?Fournisseur $fournisseur
    ): static {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getDateReglement(): ?\DateTimeImmutable
    {
        return $this->dateReglement;
    }

    public function setDateReglement(
        \DateTimeImmutable $dateReglement
    ): static {
        $this->dateReglement = $dateReglement;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(
        float|string $montant
    ): static {
        if (is_string($montant)) {
            $montant = str_replace(
                [' ', ','],
                ['', '.'],
                trim($montant)
            );
        }

        if (!is_numeric($montant)) {
            throw new \InvalidArgumentException(
                'Le montant doit être numérique.'
            );
        }

        $montantNormalise = round(
            (float) $montant,
            2
        );

        if ($montantNormalise <= 0) {
            throw new \InvalidArgumentException(
                'Le montant doit être supérieur à zéro.'
            );
        }

        $this->montant = number_format(
            $montantNormalise,
            2,
            '.',
            ''
        );

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(
        ?string $reference
    ): static {
        $reference = $reference !== null
            ? trim($reference)
            : null;

        $this->reference = $reference !== ''
            ? $reference
            : null;

        return $this;
    }

    public function getStatut(): ImportStatut
    {
        return $this->statut;
    }

    public function setStatut(
        ImportStatut $statut
    ): static {
        $this->statut = $statut;

        return $this;
    }

    public function getErreur(): ?string
    {
        return $this->erreur;
    }

    public function setErreur(
        ?string $erreur
    ): static {
        $this->erreur = $erreur;

        return $this;
    }

    public function getReglementCree(): ?ReglementFournisseur
    {
        return $this->reglementCree;
    }

    public function setReglementCree(
        ?ReglementFournisseur $reglementCree
    ): static {
        $this->reglementCree = $reglementCree;

        return $this;
    }

    public function estEnAttente(): bool
    {
        return $this->statut
            === ImportStatut::EN_ATTENTE;
    }

    public function estEnTraitement(): bool
    {
        return $this->statut
            === ImportStatut::TRAITEMENT;
    }

    public function estTraitee(): bool
    {
        return $this->statut
            === ImportStatut::TRAITEE;
    }

    public function estEnErreur(): bool
    {
        return $this->statut
            === ImportStatut::ERREUR;
    }

    public function marquerEnTraitement(): static
    {
        $this->statut = ImportStatut::TRAITEMENT;
        $this->erreur = null;

        return $this;
    }

    public function marquerTraitee(
        ReglementFournisseur $reglement
    ): static {
        $this->statut = ImportStatut::TRAITEE;
        $this->erreur = null;
        $this->reglementCree = $reglement;

        return $this;
    }

    public function marquerErreur(
        string $message
    ): static {
        $this->statut = ImportStatut::ERREUR;
        $this->erreur = $message;
        $this->reglementCree = null;

        return $this;
    }

    public function reinitialiserTraitement(): static
    {
        $this->statut = ImportStatut::EN_ATTENTE;
        $this->erreur = null;
        $this->reglementCree = null;

        return $this;
    }
}
