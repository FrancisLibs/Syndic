<?php

namespace App\Service;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;

class CalculChargesReellesService
{
    public function calculer(
        Coproprietaire $copro,
        Exercice $exercice
    ): array {

        $appelsFonds = 0.0;
        $chargesReelles = 0.0;

        // =====================
        // Appels de fonds
        // =====================

        foreach ($copro->getLigneAppelFonds() as $ligne) {
        dd($ligne);
            $appelFond = $ligne->getAppelFond();

            if ($appelFond || $appelFond->getExercice() !== $exercice) {
                continue;
            }

            $appelsFonds += (float) $ligne->getMontant();
        }

        // =====================
        // Charges réelles
        // =====================

        foreach ($copro->getRepartitions() as $repartition) {

            $ecriture = $repartition->getEcriture();

            if (
                !$ecriture
                || $ecriture->getExercice() !== $exercice
            ) {
                continue;
            }

            $chargesReelles += (float) $repartition->getMontant();
        }

        $totalAppels += $appelsFonds;
        $totalCharges += $chargesReelles;

        return [
            'appels' => $appelsFonds,
            'charges' => $chargesReelles,
            'ecart' => $chargesReelles - $appelsFonds,
        ];
    }
}
