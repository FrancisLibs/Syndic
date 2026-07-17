<?php

namespace App\Service\Cloture;

use App\Dto\AssembleeGenerale\SimulationRegularisation;
use App\Dto\AssembleeGenerale\SimulationRegularisationLigne;
use App\Entity\Exercice;
use App\Repository\CoproprietaireRepository;
use App\Repository\LigneAppelFondRepository;
use App\Repository\RepartitionRepository;

class SimulationRegularisationService
{
    public function __construct(
        private CoproprietaireRepository $coproprietaireRepository,
        private LigneAppelFondRepository $ligneAppelFondRepository,
        private RepartitionRepository $repartitionRepository,
    ) {}

    public function simulerRegularisation(
        Exercice $exercice
    ): SimulationRegularisation {
        $copropriete = $exercice->getCopropriete();

        $lignes = [];

        $coproprietaires =
            $this->coproprietaireRepository->findAll();

        foreach ($coproprietaires as $coproprietaire) {
            $tantiemes = 0;

            foreach (
                $coproprietaire->getLotCoproprietaires()
                as $lotCoproprietaire
            ) {
                $lot = $lotCoproprietaire->getLot();

                if (
                    $lot->getCopropriete()
                    !== $copropriete
                ) {
                    continue;
                }

                $tantiemes += $lot->getTantiemes();
            }

            if ($tantiemes === 0) {
                continue;
            }

            $quotePartReelle = round(
                $this->repartitionRepository
                    ->calculerTotalPourCoproprietaireEtExercice(
                        $coproprietaire,
                        $exercice
                    ),
                2
            );

            $totalAppele = round(
                (float) $this->ligneAppelFondRepository
                    ->calculerTotalAppele(
                        $exercice,
                        $coproprietaire
                    ),
                2
            );

            $lignes[] =
                new SimulationRegularisationLigne(
                    coproprietaire: $coproprietaire,
                    tantiemes: $tantiemes,
                    totalAppele: $totalAppele,
                    quotePartReelle: $quotePartReelle,
                );
        }

        return new SimulationRegularisation(
            exercice: $exercice,
            lignes: $lignes,
        );
    }
}
