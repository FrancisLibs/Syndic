<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\LigneBudget;
use App\Form\BudgetType;
use App\Form\GenererAppelFondType;
use App\Repository\BudgetRepository;
use App\Repository\ExerciceRepository;
use App\Repository\ApprobationComptesRepository;
use App\Service\GenerateurAppelFondService;
use App\Service\GenerationAppelFondService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/budget')]
final class BudgetController extends AbstractController
{
    #[Route(name: 'app_budget_index', methods: ['GET'])]
    public function index(BudgetRepository $budgetRepository): Response
    {
        return $this->render(
            'budget/index.html.twig',
            [
                'budgets' => $budgetRepository->findAll(),
            ]
        );
    }

    #[Route('/new', name: 'app_budget_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $budget = new Budget();
        $budget->setLibelle('Budget prévisionnel - Exercice .... ');
        $budget->addLigne(
            new LigneBudget()
        );
        $form = $this->createForm(BudgetType::class, $budget);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $nomExercice = $form->getData()->getExercice()->getNom();

            $budget->setLibelle('Budget prévisionnel -  ' . $nomExercice);

            $budget->setVerrouille(false);
            $entityManager->persist($budget);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_budget_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'budget/new.html.twig',
            [
                'budget' => $budget,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_budget_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(Budget $budget): Response
    {
        return $this->render(
            'budget/show.html.twig',
            [
                'budget' => $budget,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_budget_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        Budget $budget,
        EntityManagerInterface $entityManager,
    ): Response {

        if ($budget->isVerrouille()) {

            throw $this->createAccessDeniedException(
                'Budget verrouillé'
            );
        }

        $form = $this->createForm(BudgetType::class, $budget);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_budget_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'budget/edit.html.twig',
            [
                'budget' => $budget,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/generer-appel',
        name: 'app_budget_generer_appel',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function genererAppel(
        Budget $budget,
        GenerateurAppelFondService $generateur,
        GenerationAppelFondService $generationComptable,
        ExerciceRepository $exerciceRepository,
        ApprobationComptesRepository $approbationComptesRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($budget->isVerrouille()) {
            throw $this->createAccessDeniedException(
                'Budget verrouillé'
            );
        }

        $exercice = $budget->getExercice();

        $exercicePrecedent =
            $exerciceRepository->findPrecedent($exercice);

        if ($exercicePrecedent) {

            $approbation =
                $approbationComptesRepository
                ->findPourExercice($exercicePrecedent);

            if (
                !$approbation
                || !$approbation->getOperation()
            ) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        'Les comptes de l’exercice %s doivent être approuvés et régularisés avant de générer l’appel de fonds.',
                        $exercicePrecedent->getNom()
                    )
                );

                return $this->redirectToRoute(
                    'app_budget_index'
                );
            }
        }

        $form = $this->createForm(
            GenererAppelFondType::class
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateAppel = $form->get('dateAppel')->getData();
            $dateReglement = $form->get('dateReglement')->getData();

            if ($dateReglement < $dateAppel) {
                $this->addFlash(
                    'danger',
                    'La date d\'échéance ne peut pas être antérieure à la date de l\'appel.'
                );

                return $this->redirectToRoute(
                    'app_budget_generer_appel',
                    ['id' => $budget->getId()]
                );
            }

            // Création de l'appel et de ses lignes
            $appelFond = $generateur->generer(
                $budget,
                $dateAppel,
                $dateReglement
            );

            // Génération des écritures comptables
            $generationComptable->generer($appelFond);

            $budget->setVerrouille(true);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Appel de fonds généré'
            );

            return $this->redirectToRoute(
                'app_appel_fond_index'
            );
        }

        return $this->render(
            'budget/generer_appel.html.twig',
            [
                'budget' => $budget,
                'form' => $form,
            ]
        );
    }
}
