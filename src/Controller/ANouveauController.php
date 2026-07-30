<?php

namespace App\Controller;

use App\Form\SaisieANouveauType;
use App\Model\SaisieANouveau;
use App\Service\ContexteExerciceService;
use App\Service\GenerationANouveauService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/a-nouveaux')]
final class ANouveauController extends AbstractController
{
    #[Route(
        '/saisie',
        name: 'app_a_nouveau_saisie',
        methods: ['GET', 'POST']
    )]
    public function saisie(
        Request $request,
        ContexteExerciceService $contexteExercice,
        GenerationANouveauService $generationService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if ($exercice === null) {
            throw $this->createNotFoundException(
                'Aucun exercice courant n’est sélectionné.'
            );
        }

        $saisie = new SaisieANouveau();
        $saisie
            ->setExercice($exercice)
            ->setLibelle(sprintf(
                'À-nouveaux %s',
                $exercice->getNom()
            ));

        $form = $this->createForm(
            SaisieANouveauType::class,
            $saisie
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $generationService->generer($saisie);

                $this->addFlash(
                    'success',
                    'Les écritures d’à-nouveaux ont été générées.'
                );

                return $this->redirectToRoute(
                    'app_operation_index'
                );
            } catch (\RuntimeException $exception) {
                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }
        }

        return $this->render('a_nouveau/saisie.html.twig', [
            'form' => $form,
            'exercice' => $exercice,
        ]);
    }
}
