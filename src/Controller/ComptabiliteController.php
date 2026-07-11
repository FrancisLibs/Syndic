<?php

namespace App\Controller;

use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;
use App\Repository\ExerciceRepository;
use App\Enum\OperationStatut;
use App\Service\ContexteExerciceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComptabiliteController extends AbstractController
{
    #[Route('/comptabilite', name: 'app_comptabilite')]
    public function index(): Response
    {
        return $this->render(
            'comptabilite/index.html.twig',
            [
                'controller_name' => 'ComptabiliteController',
            ]
        );
    }

    #[Route('/balance', name: 'app_balance')]
    public function balance(
        ExerciceRepository $exerciceRepository,
        EcritureRepository $ecritureRepo,
        ContexteExerciceService $contexteExerciceService,
    ): Response {

        $exercice = $contexteExerciceService->getExercice();
        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice actif trouvé'
            );
        }

        $ecritures = $ecritureRepo->findBy(
            ['exercice' => $exercice]
        );

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

            //  Suppression des opérations non valides donc annulées
            if ($ecriture->getOperation()->getStatut() == OperationStatut::VALIDE) {

                $balance[$numero]['debit'] +=
                    (float) $ecriture->getDebit();

                $balance[$numero]['credit'] +=
                    (float) $ecriture->getCredit();
            }
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($balance as &$ligne) {
            $ligne['solde'] = $ligne['debit'] - $ligne['credit'];

            $totalDebit += $ligne['debit'];
            $totalCredit += $ligne['credit'];
        }
        unset($ligne);

        $equilibree = abs($totalDebit - $totalCredit) < 0.01;

        ksort($balance);

        return $this->render(
            'comptabilite/balance.html.twig',
            [
                'balance' => $balance,
                'exercice' => $exercice,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'equilibree' => $equilibree,
            ]
        );
    }

    #[Route('/grand-livre', name: 'app_grand_livre')]
    public function grandLivre(
        ExerciceRepository $exerciceRepository,
        CompteRepository $compteRepo,
        EcritureRepository $ecritureRepo,
        ContexteExerciceService $contexteExerciceService,
    ): Response {

        $exercice = $contexteExerciceService->getExercice();
        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice actif trouvé'
            );
        }

        $comptes = $compteRepo->findBy(
            [],
            ['numero' => 'ASC']
        );

        $grandLivre = [];

        foreach ($comptes as $compte) {

            $ecritures = $ecritureRepo->findBy(
                [
                    'exercice' => $exercice,
                    'compte' => $compte,
                ],
                [
                    'date' => 'ASC',
                    'id' => 'ASC',
                ]
            );

            $mouvements = [];

            $solde = 0;

            foreach ($ecritures as $ecriture) {

                $operation = $ecriture->getOperation();

                // Suppression des opérations annulées
                if ($operation->getStatut() == OperationStatut::VALIDE) {

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
                'exercice' => $exercice,
                'grandLivre' => $grandLivre,
            ]
        );
    }

    #[Route('/journal', name: 'app_journal')]
    public function journal(
        EcritureRepository $ecritureRepo,
        ContexteExerciceService $contexteExerciceService,
    ): Response {

        $exercice = $contexteExerciceService->getExercice();
        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $ecritures = $ecritureRepo->findBy(
            ['exercice' => $exercice],
            [
                'id' => 'ASC',
            ]
        );

        $lignes = [];

        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            // Suppression des opérations annulées
            if ($operation->getStatut() == OperationStatut::VALIDE) {

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
        }

        return $this->render(
            'comptabilite/journal.html.twig',
            [
                'lignes' => $lignes,
                'exercice' => $exercice,
            ]
        );
    }
}
