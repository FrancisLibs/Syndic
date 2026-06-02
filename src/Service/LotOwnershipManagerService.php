<?php

namespace App\Service;

use App\Entity\Coproprietaire;
use App\Entity\Lot;
use App\Entity\LotCoproprietaire;

class LotOwnershipManagerService
{
    public function changeOwner(
        Lot $lot,
        Coproprietaire $copro,
        ?\DateTimeInterface $date = null
    ): void {
        $date = $date ?? new \DateTimeImmutable();

        $current = $lot->getCoproprietaireActuel($date);

        if ($current && $current === $copro) {
            return;
        }

        // fermer anciens à la date donnée
        foreach ($lot->getRelationsActives($date) as $rel) {
            $rel->setDateFin($date);
        }

        // nouvelle relation
        $rel = new LotCoproprietaire();
        $rel->setLot($lot);
        $rel->setCoproprietaire($copro);
        $rel->setDateDebut($date);
        $rel->setPourcentage(100);

        $lot->addLotCoproprietaire($rel);
    }
}
