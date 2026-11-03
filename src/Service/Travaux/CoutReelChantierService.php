<?php

namespace App\Service\Travaux;

use App\Entity\Travaux\Chantier;
use App\Entity\FactureFournisseur;
use App\Enum\FactureFournisseurType;
use App\Enum\OperationStatut;
use App\Repository\FactureFournisseurRepository;

final class CoutReelChantierService
{
    public function __construct(
        private readonly FactureFournisseurRepository $factureRepository,
    ) {}

    public function calculer(
        Chantier $chantier,
        ?FactureFournisseur $factureAExclure = null
    ): float {
        $total = 0.0;

        $factures = $this->factureRepository
            ->findPourChantier($chantier);

        foreach ($factures as $facture) {

            $operationFacture = $facture->getOperation();

            if (
                $operationFacture !== null
                && $operationFacture->getStatut() === OperationStatut::ANNULE
            ) {
                continue;
            }

            // Ne pas prendre en compte la facture en cours
            if (
                $factureAExclure !== null
                && $facture === $factureAExclure
            ) {
                continue;
            }

            if ($chantier->estFinanceParAppels()) {

                $operation = $facture
                    ->getOperationRealisationTravaux();

                if (
                    $operation === null
                    || $operation->getStatut() !== OperationStatut::VALIDE
                    || !$operation->isEquilibree()
                ) {
                    throw new \LogicException(sprintf(
                        'La facture %s ne possède pas une réalisation '
                            . 'de travaux valide et équilibrée.',
                        $facture->getNumero() ?? '#' . $facture->getId()
                    ));
                }
            }

            $montant = (float) $facture->getMontant();

            if (
                $facture->getType()
                === FactureFournisseurType::AVOIR
            ) {
                $montant = -$montant;
            }

            $total += $montant;
        }

        return round($total, 2);
    }
}
