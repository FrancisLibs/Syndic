<?php

namespace App\Controller\Travaux;

use App\Entity\Travaux\Chantier;
use App\Form\Travaux\ChantierType;
use App\Repository\Travaux\ChantierRepository;
use App\Service\ContexteExerciceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chantier')]
final class ChantierController extends AbstractController
{
    #[Route(
        '/',
        name: 'app_chantier_index',
        methods: ['GET']
    )]
    public function index(
        ChantierRepository $chantierRepository,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice disponible.'
            );
        }

        $chantiers = $chantierRepository->findBy(
            ['copropriete' => $exercice->getCopropriete()],
            ['dateVote' => 'DESC']
        );

        return $this->render('chantier/index.html.twig', [
            'chantiers' => $chantiers,
            'exercice' => $exercice,
        ]);
    }

    #[Route(
        '/new',
        name: 'app_chantier_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice disponible.'
            );
        }

        $chantier = new Chantier();

        $chantier->setCopropriete(
            $exercice->getCopropriete()
        );

        $form = $this->createForm(
            ChantierType::class,
            $chantier
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($chantier);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le chantier a été créé.'
            );

            return $this->redirectToRoute(
                'app_chantier_index'
            );
        }

        return $this->render(
            'chantier/new.html.twig',
            [
                'chantier' => $chantier,
                'form' => $form,
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_chantier_show',
        methods: ['GET']
    )]
    public function show(
        Chantier $chantier,
    ): Response {
        return $this->render(
            'chantier/show.html.twig',
            [
                'chantier' => $chantier,
            ]
        );
    }
}
