<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\ExerciceRepository;
use App\Repository\PaiementRepository;
use App\Service\AffectationPaiementService;
use App\Service\GenerationPaiementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paiement')]
final class PaiementController
extends AbstractController
{
    #[Route(
        '/index',
        name: 'app_paiement_index',
        methods: ['GET']
    )]
    public function index(
        ExerciceRepository $exerciceRepository,
        PaiementRepository $paiementRepository
    ): Response {

        $exercice = $exerciceRepository->findActif();
        $paiements = $paiementRepository->findPaiementsValides();

        return $this->render(
            'paiement/index.html.twig',
            [
                'paiements' => $paiements,
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/new',
        name: 'app_paiement_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        GenerationPaiementService $generationService,
        AffectationPaiementService $affectationService,
        ExerciceRepository $exerciceRepository
    ): Response {
        $paiement = new Paiement();

        $paiement->setExercice($exerciceRepository->findActif());

        $paiement->setDatePaiement(
            new \DateTimeImmutable()
        );

        $form = $this->createForm(
            PaiementType::class,
            $paiement
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($paiement);
            $entityManager->flush();

            // =====================
            // Génération comptable
            // =====================

            $generationService->generer($paiement);

            $affectationService->affecter($paiement);

            return $this->redirectToRoute(
                'app_paiement_show',
                [
                    'id' => $paiement->getId()
                ]
            );
        }

        return $this->render(
            'paiement/new.html.twig',
            [
                'exercice' => $exercice,
                'paiement' => $paiement,
                'form' => $form,
            ]
        );
    }

    #[Route(
        'show/{id}',
        name: 'app_paiement_show',
        methods: ['GET']
    )]
    public function show(
        Paiement $paiement
    ): Response {

        return $this->render(
            'paiement/show.html.twig',
            [
                'paiement' => $paiement,
            ]
        );
    }
}
