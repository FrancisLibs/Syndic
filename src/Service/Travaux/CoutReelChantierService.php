<?php

namespace App\Service\Travaux;

use App\Entity\Travaux\Chantier;
use App\Enum\FactureFournisseurType;
use App\Enum\OperationStatut;
use App\Repository\FactureFournisseurRepository;

final class CoutReelChantierService
{
    public function __construct(
        private readonly FactureFournisseurRepository $factureRepository,
    ) {}

    public function calculer(Chantier $chantier): float
    {
        $total = 0.0;

        $factures = $this->factureRepository
            ->findPourChantier($chantier);

        foreach ($factures as $facture) {
            $operation = $facture->getOperationRealisationTravaux();

            if (
                $operation === null
                || $operation->getStatut() !== OperationStatut::VALIDE
            ) {
                throw new \LogicException(
                    sprintf(
                        'La facture %s ne possède pas une réalisation de travaux valide.',
                        $facture->getNumero() ?? '#' . $facture->getId()
                    )
                );
            }

            $montant = (float) $facture->getMontant();

            if ($facture->getType() === FactureFournisseurType::AVOIR) {
                $montant = -$montant;
            }

            $total += $montant;
        }

        return round($total, 2);
    }
}
