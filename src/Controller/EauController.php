<?php

namespace App\Controller;

use App\Entity\CompteurEau;
use App\Form\CompteurEauType;
use App\Repository\CompteurEauRepository;
use App\Repository\RepartitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ContexteExerciceService;
use App\Service\Eau\GestionRelevesCompteurService;
use App\Service\Eau\CalculConsommationService;
use App\Service\Eau\EauService;
use App\Service\Eau\GenerationRepartitionEauService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/eau')]
final class EauController extends AbstractController
{
    #[Route(
        '/compteur-general',
        name: 'app_eau_compteur_general',
        methods: ['GET', 'POST']
    )]
    public function compteurGeneral(
        Request $request,
        CompteurEauRepository $compteurEauRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $compteur = $compteurEauRepository
            ->findCompteurGeneralActif();

        if (!$compteur) {
            $compteur = new CompteurEau();

            $compteur
                ->setGeneral(true)
                ->setActif(true)
                ->setLot(null);
        }

        $form = $this->createForm(
            CompteurEauType::class,
            $compteur,
            [
                'avec_lot' => false,
                'avec_actif' => false,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Sécurité métier :
             * le compteur général ne doit jamais être
             * associé à un lot.
             */
            $compteur
                ->setGeneral(true)
                ->setActif(true)
                ->setLot(null);

            $entityManager->persist($compteur);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le compteur général a été enregistré.'
            );

            return $this->redirectToRoute(
                'app_eau_compteur_general',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'eau/compteur_general.html.twig',
            [
                'compteur' => $compteur,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/releves',
        name: 'app_eau_releves',
        methods: ['GET', 'POST']
    )]
    public function releves(
        Request $request,
        ContexteExerciceService $contexteExerciceService,
        GestionRelevesCompteurService $gestionRelevesService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        if ($request->isMethod('POST')) {
            if (
                !$this->isCsrfTokenValid(
                    'releves_eau',
                    $request->request->getString('_token')
                )
            ) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            try {
                $nombreReleves = $gestionRelevesService
                    ->enregistrer(
                        $exercice,
                        $request->request->getString(
                            'date_releve'
                        ),
                        $request->request->all(
                            'releves'
                        )
                    );

                $this->addFlash(
                    'success',
                    sprintf(
                        '%d relevé%s enregistré%s.',
                        $nombreReleves,
                        $nombreReleves > 1 ? 's' : '',
                        $nombreReleves > 1 ? 's' : ''
                    )
                );
            } catch (\DomainException $exception) {
                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }

            return $this->redirectToRoute(
                'app_eau_releves',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $donnees = $gestionRelevesService
            ->preparerAffichage(
                $exercice
            );

        return $this->render(
            'eau/releves.html.twig',
            [
                'exercice' => $exercice,
                'lignes' => $donnees['lignes'],
                'dateReleve' => $donnees['dateReleve'],
            ]
        );
    }

    #[Route(
        '/consommations',
        name: 'app_eau_consommations',
        methods: ['GET']
    )]
    public function consommations(
        ContexteExerciceService $contexteExerciceService,
        CalculConsommationService $calculConsommationService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $bilan = $calculConsommationService->calculer(
            $exercice
        );

        return $this->render(
            'eau/consommations.html.twig',
            [
                'exercice' => $exercice,
                'bilan' => $bilan,
            ]
        );
    }

    #[Route(
        '/calcul',
        name: 'app_eau_calcul',
        methods: ['GET']
    )]
    public function calcul(
        ContexteExerciceService $contexteExerciceService,
        EauService $eauService,
        RepartitionRepository $repartitionRepository,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        try {
            $calcul = $eauService->calculer($exercice);
        } catch (\DomainException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage()
            );

            return $this->redirectToRoute(
                'app_eau_releves',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $chargesEauGenerees = $repartitionRepository
            ->count([
                'exercice' => $exercice,
                'ecriture' => null,
            ]) > 0;

        return $this->render(
            'eau/calcul.html.twig',
            [
                'exercice' => $exercice,
                'calcul' => $calcul,
                'chargesEauGenerees' => $chargesEauGenerees,
            ]
        );
    }

    #[Route(
        '/generer-repartition',
        name: 'app_eau_generer_repartition',
        methods: ['POST']
    )]
    public function genererRepartition(
        ContexteExerciceService $contexteExerciceService,
        GenerationRepartitionEauService $generationService,
        Request $request,
    ): Response {
        if (
            !$this->isCsrfTokenValid(
                'generer_repartition_eau',
                $request->request->getString('_token')
            )
        ) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $exercice = $contexteExerciceService
            ->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        try {
            $generationService->generer(
                $exercice
            );

            $this->addFlash(
                'success',
                'Les charges d’eau ont été générées.'
            );
        } catch (\DomainException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_eau_calcul',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
