<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Form\CompteType;
use App\Repository\CompteRepository;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compte')]
final class CompteController extends AbstractController
{
    #[Route(name: 'app_compte_index', methods: ['GET'])]
    public function index(CompteRepository $compteRepository): Response
    {
        return $this->render(
            'compte/index.html.twig',
            [
                'comptes' => $compteRepository->findAll(),
            ]
        );
    }

    #[Route('/new', name: 'app_compte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compte = new Compte();
        $form = $this->createForm(CompteType::class, $compte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compte);
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'compte/new.html.twig',
            [
                'compte' => $compte,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_compte_show', methods: ['GET'])]
    public function show(Compte $compte): Response
    {
        return $this->render(
            'compte/show.html.twig',
            [
                'compte' => $compte,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_compte_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Compte $compte,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CompteType::class, $compte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'compte/edit.html.twig',
            [
                'compte' => $compte,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_compte_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Compte $compte,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $compte->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($compte);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route(
        '/createFournisseurCompte',
        name: 'app_compte_create_fournisseur',
        methods: ['POST']
    )]
    public function createFournisseurCompte(
        Request $request,
        EntityManagerInterface $entityManager,
        FournisseurRepository $fournisseurRepo
    ): Response {
        $fournisseurs = $fournisseurRepo->findAll();

        foreach ($fournisseurs as $fournisseur) {

            $fournisseurId = $fournisseur->getId();

            $compte = new Compte();
            $compte->setNumero('401' . str_pad($fournisseurId, 4, '0', STR_PAD_LEFT));
            $compte->setLibelle(
                'Fournisseur ' . $fournisseur->getNom()
            );
            $compte->setType(\App\Enum\CompteType::TIERS);
            $fournisseur->setCompte($compte);

            $entityManager->persist($compte);
        }
        $entityManager->flush();

        return new Response('Compte fournisseur créé avec succès', Response::HTTP_CREATED);
    }
}
