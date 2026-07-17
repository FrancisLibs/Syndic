<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Service\Cloture\ClotureExerciceService;
use App\Service\Cloture\SimulationRegularisationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClotureController extends AbstractController
{
    #[Route(
        '/exercice/{id}/assistant-cloture',
        name: 'app_cloture_workflow',
        methods: ['GET']
    )]
    public function workflow(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        $etat = $service->getEtatCloture($exercice);

        $soldes =
            $service->calculerSoldesReportables($exercice);

        $resultatExercice =
            $service->calculerResultatExercice($exercice);

        return $this->render(
            'cloture/workflow.html.twig',
            [
                'exercice' => $exercice,
                'etat' => $etat,
                'soldes' => $soldes,
                'resultatExercice' => $resultatExercice,
            ]
        );
    }

    #[Route(
        '/exercice/{id}/cloturer',
        name: 'app_cloture_exercice',
        methods: ['POST']
    )]
    public function cloturer(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        try {
            $service->cloturerExercice($exercice);

            $this->addFlash(
                'success',
                'L’exercice a été clôturé.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_cloture_workflow',
            ['id' => $exercice->getId()]
        );
    }

    #[Route(
        '/exercice/{id}/regularisations',
        name: 'app_cloture_regularisations',
        methods: ['GET']
    )]
    public function regularisations(
        Exercice $exercice,
        SimulationRegularisationService $service
    ): Response {
        $simulation =
            $service->simulerRegularisation($exercice);

        return $this->render(
            'cloture/regularisations.html.twig',
            [
                'exercice' => $exercice,
                'simulation' => $simulation,
            ]
        );
    }

    #[Route(
        '/exercice/{id}/a-nouveaux',
        name: 'app_cloture_anouveaux',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function anouveaux(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        return $this->render(
            'cloture/anouveaux.html.twig',
            [
                'exercice' => $exercice,
                'anouveaux' =>
                $service->getANouveaux($exercice),
            ]
        );
    }

    #[Route(
        '/exercice/{id}/generer-a-nouveaux',
        name: 'app_cloture_generer_anouveaux',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function genererANouveaux(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        try {
            $service->genererANouveaux($exercice);

            $this->addFlash(
                'success',
                'Les écritures d’à-nouveaux ont été générées.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_cloture_workflow',
            ['id' => $exercice->getId()]
        );
    }

    #[Route(
        '/exercice/{id}/soldes-reportables',
        name: 'app_cloture_soldes_reportables',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function soldesReportables(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        $soldes =
            $service->calculerSoldesReportables($exercice);

        return $this->render(
            'cloture/soldes_reportables.html.twig',
            [
                'exercice' => $exercice,
                'soldes' => $soldes,
            ]
        );
    }

    #[Route(
        '/exercice/{id}/cloture-comptes-gestion',
        name: 'app_cloture_comptes_gestion',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function clotureComptesGestion(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        return $this->render(
            'cloture/cloture_comptes_gestion.html.twig',
            [
                'exercice' => $exercice,
                'cloture' =>
                $service
                    ->getClotureComptesGestion($exercice),
            ]
        );
    }

    #[Route(
        '/exercice/{id}/generer-cloture-comptes-gestion',
        name: 'app_cloture_generer_comptes_gestion',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function genererClotureComptesGestion(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        try {
            $service
                ->genererClotureComptesGestion($exercice);

            $this->addFlash(
                'success',
                'Les écritures de clôture des comptes de gestion ont été générées.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_cloture_workflow',
            ['id' => $exercice->getId()]
        );
    }

    #[Route(
        '/exercice/{id}/creer-exercice-suivant',
        name: 'app_cloture_creer_exercice_suivant',
        methods: ['POST']
    )]
    public function creerExerciceSuivant(
        Exercice $exercice,
        ClotureExerciceService $service
    ): Response {
        try {
            $service->creerExerciceSuivant($exercice);

            $this->addFlash(
                'success',
                'Le nouvel exercice a été créé.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_cloture_workflow',
            ['id' => $exercice->getId()]
        );
    }
}
