<?php

namespace App\Controller\Imports;

use App\Entity\Exercice;
use App\Service\Imports\ImportReglementFournisseurService;
use App\Service\Imports\ControleImportReglementFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/imports/reglements')]
final class ImportReglementFournisseurController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_import_reglement_index',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function index(
        Exercice $exercice,
        ControleImportReglementFournisseurService $controleService,
    ): Response {
        $controle = $controleService->controler(
            $exercice
        );

        return $this->render(
            'imports/reglement/index.html.twig',
            [
                'exercice' => $exercice,
                'controle' => $controle,
            ]
        );
    }

    #[Route(
        '/{id}/importer',
        name: 'app_import_reglement_importer',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function importer(
        Exercice $exercice,
        ImportReglementFournisseurService $importService,
    ): Response {
        try {
            $importService->importer(
                $exercice
            );

            $this->addFlash(
                'success',
                'Les règlements fournisseur ont été importés.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_import_reglement_index',
            [
                'id' => $exercice->getId(),
            ]
        );
    }
}
