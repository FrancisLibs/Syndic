<?php

namespace App\Controller;

use App\Enum\OperationType;
use App\Repository\OperationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChargeController extends AbstractController
{
    #[Route('/charge', name: 'app_charge_index')]
    public function index(
        OperationRepository $operationRepo
    ): Response {

        $charges = $operationRepo->findBy(
            [
                'type' => OperationType::CHARGE
            ],
            [
                'date' => 'DESC'
            ]
        );

        $mouvements = [];

        $totalCharges = 0;

        foreach ($charges as $operation) {

            foreach ($operation->getEcritures() as $ecriture) {

                // On ne garde que les comptes de charge
                if (str_starts_with($ecriture->getCompte()->getNumero(),'6')
                ) {

                    $montant = (float) $ecriture->getDebit();

                    $totalCharges += $montant;

                    $mouvements[] = [
                        'date' => $operation->getDate(),
                        'type' => 'Charge',
                        'libelle' => $operation->getLibelle(),
                        'debit' => $montant,
                        'credit' => 0,
                    ];
                }
            }
        }

        return $this->render(
            'charge/index.html.twig',
            [
                'mvt' => $mouvements,
                'totalCharges' => $totalCharges,
            ]
        );
    }
}
