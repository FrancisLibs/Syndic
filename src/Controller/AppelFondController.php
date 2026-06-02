<?php

namespace App\Controller;

use App\Entity\AppelFond;
use App\Form\AppelFondType;
use App\Repository\AppelFondRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appel/fond')]
final class AppelFondController extends AbstractController
{
    #[Route(name: 'app_appel_fond_index', methods: ['GET'])]
    public function index(AppelFondRepository $appelFondRepository): Response
    {
        return $this->render(
            'appel_fond/index.html.twig',
            [
                'appel_fonds' => $appelFondRepository->findAll(),
            ]
        );
    }

    #[Route('/{id}', name: 'app_appel_fond_show', methods: ['GET'])]
    public function show(AppelFond $appelFond): Response
    {
        return $this->render(
            'appel_fond/show.html.twig',
            [
                'appel_fond' => $appelFond,
            ]
        );
    }
}
