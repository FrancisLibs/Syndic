<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\Repartition;
use App\Enum\ModeRepartition;

class RepartitionService
{
    public function generer(
        Ecriture $ecriture,
        array $lots,
        ModeRepartition $mode,
    ): void {
        $montant = (float) $ecriture->getMontant();

        if ($montant <= 0) {
            throw new \LogicException('Montant invalide');
        }

        if (empty($lots)) {
            throw new \LogicException('Aucun lot');
        }

        match ($mode) {
            ModeRepartition::TANTIEMES =>
            $this->repartitionTantiemes($ecriture, $lots, $montant),

            ModeRepartition::EGALITAIRE =>
            $this->repartitionEgalitaire($ecriture, $lots, $montant),
        };
    }

    private function repartitionEgalitaire(
        Ecriture $ecriture,
        array $lots,
        float $montant
    ): void {
        $nbLots = count($lots);
        $partBrute = $montant / $nbLots;

        $totalGenere = 0;

        foreach ($lots as $index => $lot) {

            if ($index === $nbLots - 1) {
                $part = $montant - $totalGenere;
            } else {
                $part = round($partBrute, 2);
                $totalGenere += $part;
            }

            $repartition = new Repartition();
            $repartition->setLot($lot);
            $repartition->setEcriture($ecriture);
            $repartition->setMontant(number_format($part, 2, '.', ''));
            $repartition->setTantiemes(0);

            $coproprietaire = $lot->getCoproprietaireActuel();
            if (!$coproprietaire) {
                throw new \LogicException(
                    'Lot sans copropriétaire actif (ID: ' . $lot->getId() . ')'
                );
            }

            $repartition->setCoproprietaire($coproprietaire);

            $ecriture->addRepartition($repartition);
        }
    }

    private function repartitionTantiemes(
        Ecriture $ecriture,
        array $lots,
        float $montant,
    ): void {
        $totalTantiemes = 0;

        // Calcule du totaldes tantièmes
        foreach ($lots as $lot) {
            $totalTantiemes += $lot->getTantiemes();
        }

        if ($totalTantiemes <= 0) {
            throw new \LogicException('Total des tantièmes invalide');
        }

        $totalGenere = 0;
        $nbLots = count($lots);

        foreach ($lots as $index => $lot) {

            if ($index === $nbLots - 1) {
                $part = $montant - $totalGenere;
            } else {
                $ratio = $lot->getTantiemes() / $totalTantiemes;
                $part = round($montant * $ratio, 2);
                $totalGenere += $part;
            }

            $repartition = new Repartition();
            $repartition->setLot($lot);
            $repartition->setEcriture($ecriture);
            $repartition->setMontant(number_format($part, 2, '.', ''));
            $repartition->setTantiemes($lot->getTantiemes());

            $coproprietaire = $lot->getCoproprietaireActuel();
            if (!$coproprietaire) {
                throw new \LogicException(
                    'Lot sans copropriétaire actif (ID: ' . $lot->getId() . ')'
                );
            }

            $repartition->setCoproprietaire($coproprietaire);

            $ecriture->addRepartition($repartition);
        }
    }
}
