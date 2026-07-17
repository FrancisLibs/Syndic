<?php

namespace App\Controller;

use App\Entity\CompteurEau;
use App\Form\CompteurEauType;
use App\Repository\CompteurEauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compteur/eau')]
final class CompteurEauController extends AbstractController
{
    #[Route(
        name: 'app_compteur_eau_index',
        methods: ['GET']
    )]
    public function index(
        CompteurEauRepository $compteurEauRepository
    ): Response {
        $compteurs = $compteurEauRepository->findBy(
            [],
            [
                'actif' => 'DESC',
                'reference' => 'ASC',
            ]
        );
        return $this->render(
            'eau/index.html.twig',
            [
                'compteurs' => $compteurs,
            ]
        );
    }

    #[Route(
        '/new',
        name: 'app_compteur_eau_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $compteurEau = new CompteurEau();

        $form = $this->createForm(
            CompteurEauType::class,
            $compteurEau
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteurEau);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le compteur d’eau a été créé.'
            );

            return $this->redirectToRoute(
                'app_compteur_eau_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'eau/new.html.twig',
            [
                'compteur_eau' => $compteurEau,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_compteur_eau_show',
        methods: ['GET']
    )]
    public function show(
        CompteurEau $compteurEau
    ): Response {
        return $this->render(
            'compteur_eau/show.html.twig',
            [
                'compteur_eau' => $compteurEau,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_compteur_eau_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        CompteurEau $compteurEau,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            CompteurEauType::class,
            $compteurEau
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le compteur d’eau a été modifié.'
            );

            return $this->redirectToRoute(
                'app_compteur_eau_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'compteur_eau/edit.html.twig',
            [
                'compteur_eau' => $compteurEau,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_compteur_eau_delete',
        methods: ['POST']
    )]
    public function delete(
        Request $request,
        CompteurEau $compteurEau,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete' . $compteurEau->getId(),
            $request->getPayload()->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        if (!$compteurEau->getReleves()->isEmpty()) {
            $this->addFlash(
                'danger',
                'Impossible de supprimer ce compteur car il possède déjà des relevés.'
            );

            return $this->redirectToRoute(
                'app_compteur_eau_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $entityManager->remove($compteurEau);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le compteur d’eau a été supprimé.'
        );

        return $this->redirectToRoute(
            'app_compteur_eau_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
