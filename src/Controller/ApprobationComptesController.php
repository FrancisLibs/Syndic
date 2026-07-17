<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Service\AssembleeGenerale\ApprobationComptesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/approbation-comptes')]
final class ApprobationComptesController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_approbation_comptes',
        methods: ['GET']
    )]
    public function index(
        Exercice $exercice,
        ApprobationComptesService $service
    ): Response {

        return $this->render(
            'approbation_comptes/index.html.twig',
            [
                'exercice' => $exercice,
                'simulation' =>
                $service->preparerApprobation($exercice),
            ]
        );
    }
}
