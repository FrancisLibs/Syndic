<?php

namespace App\Entity;

use App\Repository\FactureFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(
    repositoryClass: FactureFournisseurRepository::class
)]
class FactureFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $numero = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFacture = null;

    #[ORM\Column(
        type: Types::DATE_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $dateEcheance = null;

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

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private string $montantRegle = '0.00';

    #[ORM\Column]
    private bool $soldee = false;

    #[ORM\Column]
    private bool $comptabilisee = false;

    #[ORM\ManyToOne(
        inversedBy: 'factures'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCharge $typeCharge = null;

    #[ORM\OneToOne(
        cascade: ['persist', 'remove']
    )]
    private ?Operation $operation = null;

    #[ORM\ManyToOne(inversedBy: 'factureFournisseurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
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

    public function getDateEcheance(): ?\DateTimeImmutable
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(
        ?\DateTimeImmutable $dateEcheance
    ): static {

        $this->dateEcheance = $dateEcheance;

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

    public function getMontantRegle(): string
    {
        return $this->montantRegle;
    }

    public function setMontantRegle(
        string $montantRegle
    ): static {

        $this->montantRegle = $montantRegle;

        return $this;
    }

    public function isSoldee(): bool
    {
        return $this->soldee;
    }

    public function setSoldee(bool $soldee): static
    {
        $this->soldee = $soldee;

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

    public function getResteAPayer(): float
    {
        return (float) $this->montant
            - (float) $this->montantRegle;
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
}
