<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
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
        '/',
        name: 'app_paiement_index',
        methods: ['GET']
    )]
    public function index(
        PaiementRepository $repository
    ): Response {

        return $this->render(
            'paiement/index.html.twig',
            [
                'paiements' => $repository->findBy(
                    [],
                    ['datePaiement' => 'DESC']
                ),
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
        AffectationPaiementService $affectationService
    ): Response {

        $paiement = new Paiement();

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
                'paiement' => $paiement,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
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
