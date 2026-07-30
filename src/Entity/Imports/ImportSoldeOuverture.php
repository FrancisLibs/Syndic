<?php

namespace App\Entity\Import;

use App\Entity\Compte;
use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\ImportEtat;
use App\Repository\Import\ImportSoldeOuvertureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportSoldeOuvertureRepository::class)]
#[ORM\Table(name: 'import_solde_ouverture')]
class ImportSoldeOuverture
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
    private ?Compte $compte = null;

    /**
     * Renseigné principalement pour les comptes individuels
     * de copropriétaires.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Coproprietaire $coproprietaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private string $debit = '0.00';

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private string $credit = '0.00';

    #[ORM\Column(
        enumType: ImportEtat::class,
        options: ['default' => 'a_importer']
    )]
    private ImportEtat $etat = ImportEtat::A_IMPORTER;

    #[ORM\Column(
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $messageErreur = null;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $dateImport = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Operation $operation = null;

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

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(
        ?Compte $compte
    ): static {
        $this->compte = $compte;

        return $this;
    }

    public function getCoproprietaire(): ?Coproprietaire
    {
        return $this->coproprietaire;
    }

    public function setCoproprietaire(
        ?Coproprietaire $coproprietaire
    ): static {
        $this->coproprietaire = $coproprietaire;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(
        ?string $libelle
    ): static {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDebit(): string
    {
        return $this->debit;
    }

    public function setDebit(
        string|float|int $debit
    ): static {
        $this->debit = number_format(
            (float) $debit,
            2,
            '.',
            ''
        );

        return $this;
    }

    public function getCredit(): string
    {
        return $this->credit;
    }

    public function setCredit(
        string|float|int $credit
    ): static {
        $this->credit = number_format(
            (float) $credit,
            2,
            '.',
            ''
        );

        return $this;
    }

    public function getEtat(): ImportEtat
    {
        return $this->etat;
    }

    public function setEtat(
        ImportEtat $etat
    ): static {
        $this->etat = $etat;

        return $this;
    }

    public function getMessageErreur(): ?string
    {
        return $this->messageErreur;
    }

    public function setMessageErreur(
        ?string $messageErreur
    ): static {
        $this->messageErreur = $messageErreur;

        return $this;
    }

    public function getDateImport(): ?\DateTimeImmutable
    {
        return $this->dateImport;
    }

    public function setDateImport(
        ?\DateTimeImmutable $dateImport
    ): static {
        $this->dateImport = $dateImport;

        return $this;
    }

    public function isAImporter(): bool
    {
        return $this->etat === ImportEtat::A_IMPORTER;
    }

    public function isImporte(): bool
    {
        return $this->etat === ImportEtat::IMPORTE;
    }

    public function hasErreur(): bool
    {
        return $this->etat === ImportEtat::ERREUR;
    }

    public function marquerImporte(): static
    {
        $this->etat = ImportEtat::IMPORTE;
        $this->dateImport = new \DateTimeImmutable();
        $this->messageErreur = null;

        return $this;
    }

    public function marquerErreur(
        string $message
    ): static {
        $this->etat = ImportEtat::ERREUR;
        $this->messageErreur = $message;
        $this->dateImport = null;

        return $this;
    }

    public function reinitialiser(): static
    {
        $this->etat = ImportEtat::A_IMPORTER;
        $this->messageErreur = null;
        $this->dateImport = null;

        return $this;
    }

    public function getTotalDebit(): float
    {
        return (float) $this->debit;
    }

    public function getTotalCredit(): float
    {
        return (float) $this->credit;
    }

    public function estVide(): bool
    {
        return abs((float) $this->debit) < 0.01
            && abs((float) $this->credit) < 0.01;
    }

    public function aDebitEtCredit(): bool
    {
        return (float) $this->debit > 0
            && (float) $this->credit > 0;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): static
    {
        $this->operation = $operation;

        return $this;
    }
}
