<?php

namespace App\Entity;

use App\Repository\FactureFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\FactureFournisseurType;

#[ORM\Entity(
    repositoryClass: FactureFournisseurRepository::class
)]
class FactureFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFacture = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    #[Assert\GreaterThan(
        value: 0,
        message: 'Le montant doit être supérieur à zéro.'
    )]
    private ?string $montant = null;

    #[ORM\Column]
    private bool $comptabilisee = false;

    #[ORM\ManyToOne(
        inversedBy: 'factures',
        fetch: 'EAGER'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Coproprietaire $coproprietaireAvanceur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCharge $typeCharge = null;

    #[ORM\OneToOne(
        cascade: ['persist', 'remove']
    )]
    private ?Operation $operation = null;

    #[ORM\ManyToOne(
        inversedBy: 'factureFournisseurs',
        fetch: 'EAGER'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Column(nullable: true)]
    private ?int $volumeEau = null;

    #[ORM\Column(enumType: FactureFournisseurType::class)]
    private FactureFournisseurType $type = FactureFournisseurType::FACTURE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

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

    public function getDateFacture(): ?\DateTimeImmutable
    {
        return $this->dateFacture;
    }

    public function setDateFacture(
        \DateTimeImmutable $dateFacture
    ): static {
        $this->dateFacture = $dateFacture;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getMontantFacture(): float
    {
        return round(
            (float) $this->montant,
            2
        );
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

    public function getCoproprietaireAvanceur(): ?Coproprietaire
    {
        return $this->coproprietaireAvanceur;
    }

    public function setCoproprietaireAvanceur(
        ?Coproprietaire $coproprietaireAvanceur
    ): static {
        $this->coproprietaireAvanceur =
            $coproprietaireAvanceur;

        return $this;
    }

    public function isFactureFournisseur(): bool
    {
        return $this->fournisseur !== null;
    }

    public function getBeneficiaire(): Fournisseur|Coproprietaire|null
    {
        return $this->fournisseur
            ?? $this->coproprietaireAvanceur;
    }

    public function getTypeCharge(): ?TypeCharge
    {
        return $this->typeCharge;
    }

    public function setTypeCharge(
        ?TypeCharge $typeCharge
    ): static {
        $this->typeCharge = $typeCharge;

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

    public function isComptabilisee(): bool
    {
        return $this->comptabilisee;
    }

    public function setComptabilisee(
        bool $comptabilisee
    ): static {
        $this->comptabilisee = $comptabilisee;

        return $this;
    }

    public function getVolumeEau(): ?int
    {
        return $this->volumeEau;
    }

    public function setVolumeEau(
        ?int $volumeEau
    ): static {
        $this->volumeEau = $volumeEau;

        return $this;
    }

    public function getType(): FactureFournisseurType
    {
        return $this->type;
    }

    public function setType(
        FactureFournisseurType $type
    ): static {
        $this->type = $type;

        return $this;
    }

    public function estAvoir(): bool
    {
        return $this->type === FactureFournisseurType::AVOIR;
    }

    public function estFacture(): bool
    {
        return $this->type === FactureFournisseurType::FACTURE;
    }
}
