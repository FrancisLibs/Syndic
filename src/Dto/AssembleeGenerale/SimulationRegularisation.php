<?php

namespace App\Dto\AssembleeGenerale;

use App\Entity\Exercice;

final class SimulationRegularisation
{
    /**
     * @param SimulationRegularisationLigne[] $lignes
     */
    public function __construct(
        public readonly Exercice $exercice,
        public readonly array $lignes,
    ) {}

    public function getTotalCharges(): float
    {
        return round(
            array_sum(
                array_map(
                    static fn(
                        SimulationRegularisationLigne $ligne
                    ): float => $ligne->quotePartReelle,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getTotalAppels(): float
    {
        return round(
            array_sum(
                array_map(
                    static fn(
                        SimulationRegularisationLigne $ligne
                    ): float => $ligne->totalAppele,
                    $this->lignes
                )
            ),
            2
        );
    }

    public function getRegularisationGlobale(): float
    {
        return round(
            $this->getTotalAppels() - $this->getTotalCharges(),
            2
        );
    }

    public function getNombreLignes(): int
    {
        return count($this->lignes);
    }

    public function estEquilibree(): bool
    {
        return abs($this->getRegularisationGlobale()) < 0.01;
    }
}
