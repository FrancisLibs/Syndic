<?php

namespace App\Service\Eau;

use App\Dto\Eau\TarificationEau;
use App\Entity\Exercice;
use App\Repository\FactureFournisseurRepository;

final class TarificationEauService
{
    public function __construct(
        private readonly FactureFournisseurRepository $factureRepository,
    ) {}

    public function calculer(
        Exercice $exercice
    ): TarificationEau {
        $factures = $this->factureRepository
            ->findFacturesEauByExercice($exercice);

        $montantTotal = '0.00';
        $volumeTotal = 0;

        foreach ($factures as $facture) {
            $montantTotal = bcadd(
                $montantTotal,
                $facture->getMontant() ?? '0.00',
                2
            );

            $volumeTotal += $facture->getVolumeEau() ?? 0;
        }

        $prixM3 = null;

        if ($volumeTotal > 0) {
            $prixM3 = bcdiv(
                $montantTotal,
                (string) $volumeTotal,
                4
            );
        }

        return new TarificationEau(
            factures: $factures,
            montantTotal: $montantTotal,
            volumeTotal: $volumeTotal,
            prixM3: $prixM3,
        );
    }
}
