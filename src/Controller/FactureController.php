<?php

namespace App\Controller;

use App\Enum\OperationType;
use App\Repository\OperationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FactureController extends AbstractController
{
    #[Route('/factures', name: 'app_facture_index', methods: ['GET'])]
    public function indexFactures(
        OperationRepository $operationRepo
    ): Response {

        $factures = $operationRepo->findBy(
            [
                'type' => OperationType::CHARGE
            ],
            [
                'date' => 'DESC'
            ]
        );

        $lignes = [];

        foreach ($factures as $operation) {

            $montantFacture = 0;
            $montantPaye = 0;

            foreach ($operation->getEcritures() as $ecriture) {

                $numeroCompte = $ecriture
                    ->getCompte()
                    ?->getNumero();

                // Facture fournisseur (401 au crédit)
                if (str_starts_with($numeroCompte, '401')
                    && $ecriture->getCredit() > 0
                ) {
                    $montantFacture += $ecriture->getCredit();
                }

                // Paiement fournisseur (401 au débit)
                if (str_starts_with($numeroCompte, '401')
                    && $ecriture->getDebit() > 0
                ) {
                    $montantPaye += $ecriture->getDebit();
                }
            }

            $reste = $montantFacture - $montantPaye;

            // Statut calculé
            if ($reste <= 0) {
                $statut = 'Payée';
                $badge = 'success';
            } elseif ($montantPaye > 0) {
                $statut = 'Partiellement payée';
                $badge = 'warning';
            } else {
                $statut = 'Non payée';
                $badge = 'danger';
            }

            $lignes[] = [
                'id' => $operation->getId(),
                'date' => $operation->getDate(),
                'libelle' => $operation->getLibelle(),
                'montant' => $montantFacture,
                'paye' => $montantPaye,
                'reste' => $reste,
                'statut' => $statut,
                'badge' => $badge,
            ];
        }

        return $this->render(
            'facture/index.html.twig',
            [
                'factures' => $lignes
            ]
        );
    }
}
