<?php

namespace App\Controller\Imports;

use App\Entity\Exercice;
use App\Service\Imports\ControleImportSoldeOuvertureService;
use App\Service\Imports\ImportSoldeOuvertureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/imports/soldes-ouverture')]
final class ImportSoldeOuvertureController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_import_solde_ouverture_index',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function index(
        Exercice $exercice,
        ControleImportSoldeOuvertureService $controleService,
    ): Response {
        $controle = $controleService->controler(
            $exercice
        );

        return $this->render(
            'imports/solde_ouverture/index.html.twig',
            [
                'exercice' => $exercice,
                'controle' => $controle,
            ]
        );
    }

    #[Route(
        '/{id}/importer',
        name: 'app_import_solde_ouverture_importer',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function importer(
        Exercice $exercice,
        Request $request,
        ImportSoldeOuvertureService $importService,
    ): Response {
        $token = (string) $request->request->get(
            '_token'
        );

        if (
            !$this->isCsrfTokenValid(
                'import-soldes-ouverture-'
                    . $exercice->getId(),
                $token
            )
        ) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        try {
            $importService->importer(
                $exercice
            );

            $this->addFlash(
                'success',
                'Les soldes d’ouverture ont été importés et comptabilisés.'
            );
        } catch (\Throwable $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_import_solde_ouverture_index',
            [
                'id' => $exercice->getId(),
            ]
        );
    }
}
