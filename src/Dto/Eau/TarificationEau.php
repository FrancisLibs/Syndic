<?php

namespace App\Dto\Eau;

use App\Entity\FactureFournisseur;

final class TarificationEau
{
    /**
     * @param FactureFournisseur[] $factures
     */
    public function __construct(
        private readonly array $factures,
        private readonly string $montantTotal,
        private readonly int $volumeTotal,
        private readonly ?string $prixM3,
    ) {}

    /**
     * @return FactureFournisseur[]
     */
    public function getFactures(): array
    {
        return $this->factures;
    }

    public function getMontantTotal(): string
    {
        return $this->montantTotal;
    }

    public function getVolumeTotal(): int
    {
        return $this->volumeTotal;
    }

    public function getPrixM3(): ?string
    {
        return $this->prixM3;
    }

    public function isComplete(): bool
    {
        return $this->factures !== []
            && $this->volumeTotal > 0
            && $this->prixM3 !== null;
    }
}
