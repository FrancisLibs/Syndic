<?php

namespace App\Dto\Eau;

final class ConsommationImmeuble
{
    /**
     * @param ConsommationCompteur[] $consommations
     */
    public function __construct(
        private readonly array $consommations,
    ) {}

    /**
     * @return ConsommationCompteur[]
     */
    public function getConsommations(): array
    {
        return $this->consommations;
    }

    public function getCompteurGeneral(): ?ConsommationCompteur
    {
        foreach ($this->consommations as $consommation) {
            if ($consommation->isCompteurGeneral()) {
                return $consommation;
            }
        }

        return null;
    }

    /**
     * @return ConsommationCompteur[]
     */
    public function getCompteursIndividuels(): array
    {
        return array_values(
            array_filter(
                $this->consommations,
                static fn(
                    ConsommationCompteur $consommation
                ): bool => $consommation->isCompteurIndividuel()
            )
        );
    }

    public function getConsommationGenerale(): ?int
    {
        return $this->getCompteurGeneral()
            ?->getConsommation();
    }

    public function getConsommationLots(): int
    {
        $total = 0;

        foreach (
            $this->getCompteursIndividuels()
            as $consommation
        ) {
            if (!$consommation->isComplete()) {
                continue;
            }

            $total += $consommation->getConsommation();
        }

        return $total;
    }

    public function getConsommationCommuns(): ?int
    {
        $consommationGenerale =
            $this->getConsommationGenerale();

        if ($consommationGenerale === null) {
            return null;
        }

        return $consommationGenerale
            - $this->getConsommationLots();
    }

    public function isComplete(): bool
    {
        if ($this->consommations === []) {
            return false;
        }

        foreach ($this->consommations as $consommation) {
            if (!$consommation->isComplete()) {
                return false;
            }
        }

        return $this->getCompteurGeneral() !== null;
    }

    /**
     * @return ConsommationCompteur[]
     */
    public function getCompteursIncomplets(): array
    {
        return array_values(
            array_filter(
                $this->consommations,
                static fn(
                    ConsommationCompteur $consommation
                ): bool => !$consommation->isComplete()
            )
        );
    }

    public function hasEcartNegatif(): bool
    {
        $communs = $this->getConsommationCommuns();

        return $communs !== null && $communs < 0;
    }
}
