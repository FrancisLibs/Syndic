<?php

namespace App\Controller;

use App\Entity\FactureFournisseur;
use App\Form\FactureFournisseurType;
use App\Repository\ExerciceRepository;
use App\Repository\FactureFournisseurRepository;
use App\Service\GenerationFactureFournisseurService;
use App\Service\ReglementFactureFournisseurService;
use App\Service\ContexteExerciceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/facture/fournisseur')]
final class FactureFournisseurController extends AbstractController
{
    #[Route(
        '/index',
        name: 'app_facture_fournisseur_index',
        methods: ['GET']
    )]
    public function index(
        ExerciceRepository $exerciceRepository,
        FactureFournisseurRepository $repository,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        return $this->render(
            'facture_fournisseur/index.html.twig',
            [
                'factures' => $repository->findBy(
                    ['exercice' => $exercice],
                    ['dateFacture' => 'DESC']
                ),
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/new',
        name: 'app_facture_fournisseur_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ContexteExerciceService $contexteExerciceService,
        GenerationFactureFournisseurService $generationService,
    ): Response {

        $facture = new FactureFournisseur();

        $exercice = $contexteExerciceService->getExercice();

        $anneeExercice = substr($exercice->getNom(), -4);

        $facture->setExercice($exercice);

        $facture->setDateFacture(new \DateTimeImmutable());

        $form = $this->createForm(FactureFournisseurType::class, $facture);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($facture);
            $entityManager->flush();

            // =====================
            // Génération comptable
            // =====================

            $generationService->generer($facture);

            return $this->redirectToRoute(
                'app_facture_fournisseur_show',
                [
                    'id' => $facture->getId()
                ]
            );
        }

        return $this->render(
            'facture_fournisseur/new.html.twig',
            [
                'exercice' => $exercice,
                'facture' => $facture,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_facture_fournisseur_show',
        methods: ['GET']
    )]
    public function show(
        FactureFournisseur $facture
    ): Response {
        return $this->render(
            'facture_fournisseur/show.html.twig',
            [
                'facture_fournisseur' => $facture,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_facture_fournisseur_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        FactureFournisseur $facture,
        EntityManagerInterface $entityManager
    ): Response {
        if ($facture->isComptabilisee()) {

            $this->addFlash(
                'warning',
                'Une facture comptabilisée ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_facture_fournisseur_show',
                [
                    'id' => $facture->getId()
                ]
            );
        }

        $form = $this->createForm(
            FactureFournisseurType::class,
            $facture
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {

            $entityManager->flush();

            return $this->redirectToRoute(
                'app_facture_fournisseur_index',
            );
        }

        return $this->render(
            'facture_fournisseur/edit.html.twig',
            [
                'facture' => $facture,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_facture_fournisseur_delete',
        methods: ['POST']
    )]
    public function delete(
        Request $request,
        FactureFournisseur $facture,
        EntityManagerInterface $entityManager
    ): Response {

        if ($facture->isComptabilisee()) {

            throw $this->createAccessDeniedException(
                'Facture comptabilisée'
            );
        }

        if (
            $this->isCsrfTokenValid(
                'delete' . $facture->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {

            $entityManager->remove($facture);

            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_facture_fournisseur_index'
        );
    }

    #[Route(
        '/{id}/regler',
        name: 'app_facture_fournisseur_regler',
        methods: ['POST']
    )]
    public function regler(
        FactureFournisseur $facture,
        ReglementFactureFournisseurService $service
    ): Response {

        $service->regler($facture);

        return $this->redirectToRoute(
            'app_facture_fournisseur_show',
            [
                'id' => $facture->getId()
            ]
        );
    }
}
