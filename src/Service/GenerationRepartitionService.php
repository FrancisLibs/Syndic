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

        $lots = $copropriete->getLots();

        if (count($lots) === 0) {
            return;
        }

        $totalTantiemes = 0;
        $totalReparti = 0;

        foreach ($lots as $lot) {
            $totalTantiemes += $lot->getTantiemes();
        }

        foreach ($lots as $lot) {

            $copro = $lot->getCoproprietaireActuel(
                $facture->getDateFacture()
            );

            if (!$copro) {
                continue;
            }

            $montant = round(
                (
                    (float) $facture->getMontant()
                    * $lot->getTantiemes()
                ) / $totalTantiemes,
                2
            );

            $totalReparti += $montant;

            $repartition = new Repartition();
            $repartition->setLot($lot);
            $repartition->setCoproprietaire($copro);
            $repartition->setEcriture($ecritureCharge);
            $repartition->setTantiemes($lot->getTantiemes());
            $repartition->setMontant(
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
