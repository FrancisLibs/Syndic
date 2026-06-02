<?php

namespace App\Controller;

use App\Repository\OperationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        OperationRepository $operationRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            // On récupère les 10 dernières opérations pour ne pas surcharger
            'operations' => $operationRepository->findBy([], ['date' => 'DESC'], 10),
        ]);
    }
}
