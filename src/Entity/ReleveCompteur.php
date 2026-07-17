<?php

namespace App\Entity;

use App\Repository\ReleveCompteurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReleveCompteurRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_RELEVE_COMPTEUR_EXERCICE',
    columns: ['compteur_id', 'exercice_id']
)]
class ReleveCompteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'releves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CompteurEau $compteur = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateReleve = null;

    #[ORM\Column]
    private ?int $valeurIndex = null;

    #[ORM\ManyToOne(inversedBy: 'relevesCompteur')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompteur(): ?CompteurEau
    {
        return $this->compteur;
    }

    public function setCompteur(?CompteurEau $compteur): static
    {
        $this->compteur = $compteur;

        return $this;
    }

    public function getDateReleve(): ?\DateTimeImmutable
    {
        return $this->dateReleve;
    }

    public function setDateReleve(
        \DateTimeImmutable $dateReleve
    ): static {
        $this->dateReleve = $dateReleve;

        return $this;
    }

    public function getValeurIndex(): ?int
    {
        return $this->valeurIndex;
    }

    public function setValeurIndex(int $valeurIndex): static
    {
        if ($valeurIndex < 0) {
            throw new \InvalidArgumentException(
                'La valeur de l’index ne peut pas être négative.'
            );
        }

        $this->valeurIndex = $valeurIndex;

        return $this;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
