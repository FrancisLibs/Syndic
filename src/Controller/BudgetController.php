<?php

namespace App\Controller;

use App\Entity\AppelFond;
use App\Entity\Budget;
use App\Entity\LigneBudget;
use App\Form\BudgetType;
use App\Repository\BudgetRepository;
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
        $budget->setLibelle('Budget prévisionnel ');
        $budget->addLigne(
            new LigneBudget()
        );
        $form = $this->createForm(BudgetType::class, $budget);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}', name: 'app_budget_show', methods: ['GET'])]
    public function show(Budget $budget): Response
    {
        return $this->render(
            'budget/show.html.twig',
            [
                'budget' => $budget,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_budget_edit', methods: ['GET', 'POST'])]
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
        methods: ['POST']
    )]
    public function genererAppel(
        Budget $budget,
        GenerateurAppelFondService $generateur,
        GenerationAppelFondService $generationComptable,
        EntityManagerInterface $entityManager,
    ): Response {

        if ($budget->isVerrouille()) {

            throw $this->createAccessDeniedException(
                'Budget verrouillé'
            );
        }

        // =====================
        // Génération appel métier
        // =====================

        $appelFond = $generateur->generer(
            $budget,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+30 days')
        );

        // =====================
        // Génération comptable
        // =====================

        $generationComptable
            ->generer($appelFond);

        $this->addFlash(
            'success',
            'Appel de fonds généré'
        );

        $budget->setVerrouille(true);

        $entityManager->flush();

        return $this->redirectToRoute(
            'app_appel_fond_show',
            [
                'id' => $appelFond->getId()
            ]
        );
    }
}
