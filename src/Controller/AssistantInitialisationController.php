<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/initialisation')]
final class AssistantInitialisationController extends AbstractController
{
    #[Route(
        '',
        name: 'app_initialisation'
    )]
    public function index(): Response
    {
        return $this->render(
            'initialisation/index.html.twig'
        );
    }
}
