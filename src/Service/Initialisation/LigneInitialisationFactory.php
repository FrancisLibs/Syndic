<?php

namespace App\Service\Initialisation;

use App\Dto\Initialisation\InitialisationComptable;
use App\Dto\Initialisation\LigneInitialisation;
use App\Entity\Compte;
use App\Entity\Coproprietaire;

final class LigneInitialisationFactory
{
    public function ajouter(
        InitialisationComptable $initialisation,
        Compte $compte,
        float $solde,
        ?Coproprietaire $coproprietaire = null,
        ?string $libelle = null,
        bool $ignorerSiZero = true,
    ): void {
        if (
            $ignorerSiZero
            && abs($solde) < 0.01
        ) {
            return;
        }

        if ($solde >= 0) {
            $ligne = LigneInitialisation::debit(
                $compte,
                $solde,
                $coproprietaire,
                $libelle
            );
        } else {
            $ligne = LigneInitialisation::credit(
                $compte,
                abs($solde),
                $coproprietaire,
                $libelle
            );
        }

        $initialisation->ajouterLigne($ligne);
    }
}
