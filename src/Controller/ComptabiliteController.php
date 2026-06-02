<?php

namespace App\Controller;

use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComptabiliteController extends AbstractController
{
    #[Route('/comptabilite', name: 'app_comptabilite')]
    public function index(): Response
    {
        return $this->render('comptabilite/index.html.twig', [
            'controller_name' => 'ComptabiliteController',
        ]);
    }

    #[Route('/balance', name: 'app_balance')]
    public function balance(EcritureRepository $ecritureRepo): Response
    {
        $ecritures = $ecritureRepo->findAll();

        $balance = [];

        foreach ($ecritures as $ecriture) {

            $compte = $ecriture->getCompte();

            $numero = $compte->getNumero();

            if (!isset($balance[$numero])) {

                $balance[$numero] = [
                    'numero' => $numero,
                    'libelle' => $compte->getLibelle(),
                    'debit' => 0,
                    'credit' => 0,
                ];
            }

            $balance[$numero]['debit'] +=
                (float) $ecriture->getDebit();

            $balance[$numero]['credit'] +=
                (float) $ecriture->getCredit();
        }

        foreach ($balance as &$ligne) {
            $ligne['solde']
                = $ligne['debit'] - $ligne['credit'];
        }

        ksort($balance);

        return $this->render(
            'comptabilite/balance.html.twig',
            [
                'balance' => $balance,
            ]
        );
    }

    #[Route('/grand-livre', name: 'app_grand_livre')]
    public function grandLivre(
        CompteRepository $compteRepo,
        EcritureRepository $ecritureRepo
    ): Response {

        $comptes = $compteRepo->findBy(
            [],
            ['numero' => 'ASC']
        );

        $grandLivre = [];

        foreach ($comptes as $compte) {

            $ecritures = $ecritureRepo->findBy(
                ['compte' => $compte],
                ['id' => 'ASC']
            );

            $mouvements = [];

            $solde = 0;

            foreach ($ecritures as $ecriture) {

                $operation = $ecriture->getOperation();

                $debit = (float) $ecriture->getDebit();
                $credit = (float) $ecriture->getCredit();

                $solde += $debit;
                $solde -= $credit;

                $mouvements[] = [
                    'exercice' => $ecriture->getExercice()->getNom(),

                    'date' => $operation->getDate(),

                    'libelle' => $operation->getLibelle(),

                    'debit' => $debit,

                    'credit' => $credit,

                    'solde' => $solde,
                ];
            }

            $grandLivre[] = [

                'compte' => $compte,

                'mouvements' => $mouvements,

                'totalDebit' => array_sum(
                    array_column($mouvements, 'debit')
                ),

                'totalCredit' => array_sum(
                    array_column($mouvements, 'credit')
                ),

                'solde' => $solde,
            ];
        }

        return $this->render(
            'comptabilite/grand_livre.html.twig',
            [
                'grandLivre' => $grandLivre,
            ]
        );
    }

    #[Route('/journal', name: 'app_journal')]
    public function journal(
        EcritureRepository $ecritureRepo
    ): Response {

        $ecritures = $ecritureRepo->findBy(
            [],
            ['id' => 'ASC']
        );

        $lignes = [];

        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            $compte = $ecriture->getCompte();

            $lignes[] = [
                'exercice' => $ecriture->getExercice()->getNom(),

                'date' => $operation->getDate(),

                'type' => $operation->getType()?->value,

                'libelle' => $operation->getLibelle(),

                'compte' => $compte->getNumero(),

                'compteLibelle' => $compte->getLibelle(),

                'debit' => (float) $ecriture->getDebit(),

                'credit' => (float) $ecriture->getCredit(),
            ];
        }

        return $this->render(
            'comptabilite/journal.html.twig',
            [
                'lignes' => $lignes,
            ]
        );
    }
}
