<?php

namespace App\Entity\Imports;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use App\Enum\ImportStatut;
use App\Repository\Import\ImportFactureFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: ImportFactureFournisseurRepository::class
)]
class ImportFactureFournisseur
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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCharge $typeCharge = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Coproprietaire $coproprietaireAvanceur = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFacture = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2
    )]
    private ?string $montant = null;

    #[ORM\Column(nullable: true)]
    private ?int $volumeEau = null;

    #[ORM\Column]
    private bool $reglee = false;

    #[ORM\Column(
        type: Types::DATE_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $dateReglement = null;

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
    private ?FactureFournisseur $factureCreee = null;

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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(
        ?string $numero
    ): static {
        $numero = $numero !== null
            ? trim($numero)
            : null;

        $this->numero = $numero !== ''
            ? $numero
            : null;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(
        string $libelle
    ): static {
        $this->libelle = trim($libelle);

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
                $montant
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

        $this->montant = number_format(
            $montantNormalise,
            2,
            '.',
            ''
        );

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

    public function isReglee(): bool
    {
        return $this->reglee;
    }

    public function setReglee(
        bool $reglee
    ): static {
        $this->reglee = $reglee;

        if (!$reglee) {
            $this->dateReglement = null;
        }

        return $this;
    }

    public function getDateReglement(): ?\DateTimeImmutable
    {
        return $this->dateReglement;
    }

    public function setDateReglement(
        ?\DateTimeImmutable $dateReglement
    ): static {
        $this->dateReglement = $dateReglement;

        if ($dateReglement !== null) {
            $this->reglee = true;
        }

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

    public function getFactureCreee(): ?FactureFournisseur
    {
        return $this->factureCreee;
    }

    public function setFactureCreee(
        ?FactureFournisseur $factureCreee
    ): static {
        $this->factureCreee = $factureCreee;

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
        FactureFournisseur $facture
    ): static {
        $this->statut = ImportStatut::TRAITEE;
        $this->erreur = null;
        $this->factureCreee = $facture;

        return $this;
    }

    public function marquerErreur(
        string $message
    ): static {
        $this->statut = ImportStatut::ERREUR;
        $this->erreur = $message;
        $this->factureCreee = null;

        return $this;
    }

    public function reinitialiserTraitement(): static
    {
        $this->statut = ImportStatut::EN_ATTENTE;
        $this->erreur = null;
        $this->factureCreee = null;

        return $this;
    }
}
