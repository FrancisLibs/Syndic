<?php

namespace App\Dto\AssembleeGenerale;

use App\Entity\Exercice;

final class ApprobationComptes
{
    /**
     * @param ApprobationLigne[] $lignes
     */
    public function __construct(
        public readonly Exercice $exercice,
        public readonly array $lignes,
    ) {}

    public function getTotalAppele(): float
    {
        return round(
            array_sum(
                array_map(
                    fn(ApprobationLigne $ligne)
                    => $ligne->totalAppele,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getTotalCharges(): float
    {
        return round(
            array_sum(
                array_map(
                    fn(ApprobationLigne $ligne)
                    => $ligne->quotePartReelle,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getTotalDebiteur(): float
    {
        return round(
            array_sum(
                array_map(
                    fn(ApprobationLigne $ligne)
                    => $ligne->estDebiteur()
                        ? $ligne->getMontant()
                        : 0,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getTotalCrediteur(): float
    {
        return round(
            array_sum(
                array_map(
                    fn(ApprobationLigne $ligne)
                    => $ligne->estCrediteur()
                        ? $ligne->getMontant()
                        : 0,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getSolde489000(): float
    {
        return round(
            $this->getTotalDebiteur()
                - $this->getTotalCrediteur(),
            2
        );
    }

    public function getNombreLignes(): int
    {
        return count($this->lignes);
    }

    public function estEquilibree(): bool
    {
        return abs(
            $this->getTotalAppele()
                - $this->getTotalCharges()
        ) < 0.01;
    }
}
