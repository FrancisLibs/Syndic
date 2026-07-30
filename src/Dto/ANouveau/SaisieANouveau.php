<?php

namespace App\Dto\ANouveau;

use App\Entity\Exercice;
use Symfony\Component\Validator\Constraints as Assert;

final class SaisieANouveau
{
    #[Assert\NotNull(
        message: 'L’exercice est obligatoire.'
    )]
    private ?Exercice $exercice = null;

    #[Assert\NotBlank(
        message: 'Le libellé est obligatoire.'
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $libelle = null;

    /**
     * @var array<int, ANouveauLigne>
     */
    #[Assert\Valid]
    #[Assert\Count(
        min: 2,
        minMessage: 'Au moins deux lignes sont nécessaires.'
    )]
    private array $lignes = [];

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(
        ?Exercice $exercice
    ): self {
        $this->exercice = $exercice;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(
        ?string $libelle
    ): self {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return array<int, ANouveauLigne>
     */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    /**
     * @param array<int, ANouveauLigne> $lignes
     */
    public function setLignes(
        array $lignes
    ): self {
        $this->lignes = $lignes;

        return $this;
    }

    public function addLigne(
        ANouveauLigne $ligne
    ): self {
        $this->lignes[] = $ligne;

        return $this;
    }

    public function removeLigne(
        ANouveauLigne $ligne
    ): self {
        $index = array_search(
            $ligne,
            $this->lignes,
            true
        );

        if ($index !== false) {
            unset($this->lignes[$index]);

            $this->lignes = array_values(
                $this->lignes
            );
        }

        return $this;
    }

    public function calculerTotalDebit(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            $solde = $ligne->getSoldeFloat();

            if ($solde > 0) {
                $total += $solde;
            }
        }

        return round($total, 2);
    }

    public function calculerTotalCredit(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            $solde = $ligne->getSoldeFloat();

            if ($solde < 0) {
                $total += abs($solde);
            }
        }

        return round($total, 2);
    }

    public function estEquilibree(): bool
    {
        return abs(
            $this->calculerTotalDebit()
                - $this->calculerTotalCredit()
        ) < 0.01;
    }
}
