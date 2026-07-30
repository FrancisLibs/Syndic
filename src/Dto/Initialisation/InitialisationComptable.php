<?php

namespace App\Dto\Initialisation;

use App\Entity\Exercice;

final class InitialisationComptable
{
    /**
     * @var LigneInitialisation[]
     */
    private array $lignes = [];

    /**
     * @var ControleInitialisation[]
     */
    private array $controles = [];

    public function __construct(
        private readonly Exercice $exercice,
        private readonly \DateTimeImmutable $date,
        private readonly string $libelle = 'Écriture d\'ouverture',
    ) {}

    public function getExercice(): Exercice
    {
        return $this->exercice;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function ajouterLigne(
        LigneInitialisation $ligne,
    ): self {
        if (!$ligne->estVide()) {
            $this->lignes[] = $ligne;
        }

        return $this;
    }

    /**
     * @return LigneInitialisation[]
     */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    public function ajouterControle(
        ControleInitialisation $controle,
    ): self {
        $this->controles[] = $controle;

        return $this;
    }

    /**
     * @return ControleInitialisation[]
     */
    public function getControles(): array
    {
        return $this->controles;
    }

    public function getTotalDebit(): float
    {
        $total = array_reduce(
            $this->lignes,
            static fn(
                float $total,
                LigneInitialisation $ligne,
            ): float => $total + $ligne->getDebit(),
            0.0
        );

        return round($total, 2);
    }

    public function getTotalCredit(): float
    {
        $total = array_reduce(
            $this->lignes,
            static fn(
                float $total,
                LigneInitialisation $ligne,
            ): float => $total + $ligne->getCredit(),
            0.0
        );

        return round($total, 2);
    }

    public function getEcart(): float
    {
        return round(
            $this->getTotalDebit()
                - $this->getTotalCredit(),
            2
        );
    }

    public function isEquilibree(): bool
    {
        return abs($this->getEcart()) < 0.01;
    }

    public function hasLignes(): bool
    {
        return count($this->lignes) > 0;
    }

    public function hasErreurs(): bool
    {
        foreach ($this->controles as $controle) {
            if (!$controle->isValide()) {
                return true;
            }
        }

        return false;
    }

    public function peutEtreCreee(): bool
    {
        return $this->hasLignes()
            && $this->isEquilibree()
            && !$this->hasErreurs();
    }

    /**
     * @return LigneInitialisation[]
     */
    public function getLignesDebit(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(
                    LigneInitialisation $ligne,
                ): bool => $ligne->estDebit()
            )
        );
    }

    /**
     * @return LigneInitialisation[]
     */
    public function getLignesCredit(): array
    {
        return array_values(
            array_filter(
                $this->lignes,
                static fn(
                    LigneInitialisation $ligne,
                ): bool => $ligne->estCredit()
            )
        );
    }
}
