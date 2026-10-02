<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Entity\Repartition;
use Doctrine\ORM\EntityManagerInterface;

class GenerationRepartitionService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function generer(
        Ecriture $ecritureCharge,
        Exercice $exercice,
        \DateTimeInterface $dateRepartition,
        float $montantARepartir
    ): void {
        $copropriete = $exercice->getCopropriete();

        $lots = $copropriete
            ->getLots()
            ->toArray();

        if ($lots === []) {
            throw new \LogicException(
                'Aucun lot n’est défini pour cette copropriété.'
            );
        }

        /*
         * Calcul du total des tantièmes.
         */
        $totalTantiemes = 0;

        foreach ($lots as $lot) {
            $tantiemes = $lot->getTantiemes();

            if (
                $tantiemes === null
                || $tantiemes <= 0
            ) {
                throw new \LogicException(
                    sprintf(
                        'Les tantièmes du lot %s sont invalides.',
                        $lot->getReference()
                    )
                );
            }

            $totalTantiemes += $tantiemes;
        }

        if ($totalTantiemes <= 0) {
            throw new \LogicException(
                'Le total des tantièmes est invalide.'
            );
        }

        /*
         * Recherche du copropriétaire de chaque lot
         * à la date de la charge.
         */
        $lotsARepartir = [];

        foreach ($lots as $lot) {
            $coproprietaire =
                $lot->getCoproprietaireActuel(
                    $dateRepartition
                );

            if ($coproprietaire === null) {
                throw new \LogicException(
                    sprintf(
                        'Aucun copropriétaire actif pour le lot %s à la date du %s.',
                        $lot->getReference(),
                        $dateRepartition->format('d/m/Y')
                    )
                );
            }

            $lotsARepartir[] = [
                'lot' => $lot,
                'coproprietaire' => $coproprietaire,
            ];
        }

        /*
         * Répartition.
         */
        $totalReparti = 0.0;

        $dernierIndex =
            count($lotsARepartir) - 1;

        foreach (
            $lotsARepartir
            as $index => $ligne
        ) {
            $lot = $ligne['lot'];

            $coproprietaire =
                $ligne['coproprietaire'];

            /*
             * Le dernier lot absorbe
             * l'éventuel écart d'arrondi.
             */
            if ($index === $dernierIndex) {
                $montant = round(
                    $montantARepartir
                        - $totalReparti,
                    2
                );
            } else {
                $montant = round(
                    $montantARepartir
                        * $lot->getTantiemes()
                        / $totalTantiemes,
                    2
                );

                $totalReparti += $montant;
            }

            $repartition =
                new Repartition();

            $repartition
                ->setLot($lot)
                ->setCoproprietaire(
                    $coproprietaire
                )
                ->setEcriture(
                    $ecritureCharge
                )
                ->setExercice(
                    $exercice
                )
                ->setTantiemes(
                    $lot->getTantiemes()
                )
                ->setMontant(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

            $this->em->persist(
                $repartition
            );
        }
    }
}
