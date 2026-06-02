<?php

namespace App\Controller;

use App\Entity\TypeCharge;
use App\Form\TypeChargeType;
use App\Repository\TypeChargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type/charge')]
final class TypeChargeController extends AbstractController
{
    #[Route(name: 'app_type_charge_index', methods: ['GET'])]
    public function index(TypeChargeRepository $typeChargeRepository): Response
    {
        return $this->render('type_charge/index.html.twig', [
            'type_charges' => $typeChargeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_type_charge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeCharge = new TypeCharge();
        $form = $this->createForm(TypeChargeType::class, $typeCharge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeCharge);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_type_charge_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('type_charge/new.html.twig', [
            'type_charge' => $typeCharge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_charge_show', methods: ['GET'])]
    public function show(TypeCharge $typeCharge): Response
    {
        return $this->render('type_charge/show.html.twig', [
            'type_charge' => $typeCharge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_charge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeCharge $typeCharge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypeChargeType::class, $typeCharge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_type_charge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'type_charge/edit.html.twig',
            [
                'type_charge' => $typeCharge,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_type_charge_delete', methods: ['POST'])]
    public function delete(Request $request, TypeCharge $typeCharge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $typeCharge->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($typeCharge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_type_charge_index', [], Response::HTTP_SEE_OTHER);
    }
}
