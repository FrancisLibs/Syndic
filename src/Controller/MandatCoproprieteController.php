<?php

namespace App\Controller;

use App\Entity\MandatCopropriete;
use App\Form\MandatCoproprieteType;
use App\Repository\MandatCoproprieteRepository;
use App\Service\ContexteExerciceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mandats')]
class MandatCoproprieteController extends AbstractController
{
    #[Route('', name: 'app_mandat_copropriete_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ContexteExerciceService $contexteExerciceService,
        MandatCoproprieteRepository $mandatRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $copropriete = $exercice->getCopropriete();

        $mandat = new MandatCopropriete();
        $mandat->setCopropriete($copropriete);

        $form = $this->createForm(
            MandatCoproprieteType::class,
            $mandat
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mandat);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le mandat a été enregistré.'
            );

            return $this->redirectToRoute(
                'app_mandat_copropriete_index'
            );
        }

        $mandats = $mandatRepository->findBy(
            ['copropriete' => $copropriete],
            ['dateDebut' => 'DESC']
        );

        return $this->render(
            'mandat_copropriete/index.html.twig',
            [
                'exercice' => $exercice,
                'mandats' => $mandats,
                'form' => $form,
            ]
        );
    }
}
