<?php

namespace App\Service\Travaux;

use App\Entity\Travaux\Chantier;
use App\Enum\ModeRepartition;

final class SimulationClotureChantierService
{
    public function simuler(
        Chantier $chantier,
        float $coutReel
    ): array {

        if (!is_finite($coutReel) || $coutReel < 0) {
            throw new \InvalidArgumentException(
                'Le coût réel du chantier doit être positif ou nul.'
            );
        }
        
        $lignes = [];

        // Récupération des appels individuels du chantier
        foreach ($chantier->getAppels() as $appel) {
            foreach ($appel->getLigneAppelFonds() as $ligne) {
                $lot = $ligne->getLot();
                $coproprietaire = $ligne->getCoproprietaire();

                if ($lot === null || $coproprietaire === null) {
                    throw new \LogicException(
                        'Une ligne d’appel est incomplète.'
                    );
                }

                $cle = $coproprietaire->getId()
                    . '-' . $lot->getId();

                if (!isset($lignes[$cle])) {
                    $lignes[$cle] = [
                        'coproprietaire' => $coproprietaire,
                        'lot' => $lot,
                        'tantiemes' => (int) $lot->getTantiemes(),
                        'appels' => 0.0,
                        'quotePart' => 0.0,
                        'regularisation' => 0.0,
                    ];
                }

                $lignes[$cle]['appels'] +=
                    (float) $ligne->getMontant();
            }
        }

        if ($lignes === []) {
            throw new \LogicException(
                'Aucun appel de fonds trouvé pour ce chantier.'
            );
        }

        $totalTantiemes = array_sum(
            array_column($lignes, 'tantiemes')
        );

        $nombreLots = count($lignes);

        if (
            $chantier->getModeRepartition() === ModeRepartition::TANTIEMES
            && $totalTantiemes <= 0
        ) {
            throw new \LogicException(
                'Le total des tantièmes est invalide.'
            );
        }

        $cles = array_keys($lignes);
        $derniereCle = end($cles);

        $resteCentimes = (int) round($coutReel * 100);

        foreach ($lignes as $cle => &$ligne) {
            if ($cle === $derniereCle) {
                $centimes = $resteCentimes;
            } else {
                $proportion = match ($chantier->getModeRepartition()) {
                    ModeRepartition::TANTIEMES =>
                    $ligne['tantiemes'] / $totalTantiemes,
                    ModeRepartition::EGALITAIRE =>
                    1 / $nombreLots,
                };

                $centimes = (int) round(
                    $coutReel * $proportion * 100
                );
            }

            $resteCentimes -= $centimes;

            $ligne['quotePart'] = $centimes / 100;

            $ligne['appels'] = round(
                $ligne['appels'],
                2
            );

            $ligne['regularisation'] = round(
                $ligne['appels'] - $ligne['quotePart'],
                2
            );
        }
        unset($ligne);

        // Regroupement par copropriétaire
        $coproprietaires = [];

        foreach ($lignes as $ligne) {
            $coproprietaire = $ligne['coproprietaire'];
            $id = $coproprietaire->getId();

            if (!isset($coproprietaires[$id])) {
                $coproprietaires[$id] = [
                    'coproprietaire' => $coproprietaire,
                    'appels' => 0.0,
                    'quotePart' => 0.0,
                    'regularisation' => 0.0,
                ];
            }

            $coproprietaires[$id]['appels'] +=
                $ligne['appels'];

            $coproprietaires[$id]['quotePart'] +=
                $ligne['quotePart'];

            $coproprietaires[$id]['regularisation'] +=
                $ligne['regularisation'];
        }

        foreach ($coproprietaires as &$ligne) {
            $ligne['appels'] = round($ligne['appels'], 2);
            $ligne['quotePart'] = round($ligne['quotePart'], 2);
            $ligne['regularisation'] = round(
                $ligne['regularisation'],
                2
            );
        }
        unset($ligne);

        return [
            'lignes' => array_values($coproprietaires),
            'totalAppels' => round(
                array_sum(array_column($lignes, 'appels')),
                2
            ),
            'coutReel' => round($coutReel, 2),
            'regularisationTotale' => round(
                array_sum(
                    array_column($coproprietaires, 'regularisation')
                ),
                2
            ),
        ];
    }
}
