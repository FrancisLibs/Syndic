<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\FactureFournisseur;
use App\Entity\Repartition;
use Doctrine\ORM\EntityManagerInterface;

class GenerationRepartitionService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function generer(
        Ecriture $ecritureCharge,
        FactureFournisseur $facture
    ): void {
        $copropriete = $facture
            ->getExercice()
            ->getCopropriete();

        $lots = $copropriete->getLots()->toArray();

        if ($lots === []) {
            throw new \LogicException(
                'Aucun lot n’est défini pour cette copropriété.'
            );
        }

        $totalTantiemes = 0;

        foreach ($lots as $lot) {
            $tantiemes = $lot->getTantiemes();

            if ($tantiemes === null || $tantiemes <= 0) {
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
         * On prépare uniquement les lots ayant un copropriétaire
         * à la date de la facture.
         */
        $lotsARepartir = [];

        foreach ($lots as $lot) {
            $coproprietaire = $lot->getCoproprietaireActuel(
                $facture->getDateFacture()
            );

            if ($coproprietaire === null) {
                throw new \LogicException(
                    sprintf(
                        'Aucun copropriétaire actif pour le lot %s à la date du %s.',
                        $lot->getReference(),
                        $facture->getDateFacture()->format('d/m/Y')
                    )
                );
            }

            $lotsARepartir[] = [
                'lot' => $lot,
                'coproprietaire' => $coproprietaire,
            ];
        }

        $montantFacture = (float) $facture->getMontant();
        $totalReparti = 0.0;
        $dernierIndex = count($lotsARepartir) - 1;

        foreach ($lotsARepartir as $index => $ligne) {
            $lot = $ligne['lot'];
            $coproprietaire = $ligne['coproprietaire'];

            if ($index === $dernierIndex) {
                /*
                 * Le dernier lot absorbe l’éventuel écart
                 * causé par les arrondis.
                 */
                $montant = round(
                    $montantFacture - $totalReparti,
                    2
                );
            } else {
                $montant = round(
                    $montantFacture
                        * $lot->getTantiemes()
                        / $totalTantiemes,
                    2
                );

                $totalReparti += $montant;
            }

            $repartition = new Repartition();

            $repartition
                ->setLot($lot)
                ->setCoproprietaire($coproprietaire)
                ->setEcriture($ecritureCharge)
                ->setExercice($facture->getExercice())
                ->setTantiemes($lot->getTantiemes())
                ->setMontant(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

            $this->em->persist($repartition);
        }
    }
}
