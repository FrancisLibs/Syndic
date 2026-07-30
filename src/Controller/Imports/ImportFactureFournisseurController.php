<?php

namespace App\Controller\Imports;

use App\Entity\Exercice;
use App\Service\Imports\ControleImportFactureFournisseurService;
use App\Service\Imports\ImportFactureFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/imports/factures')]
final class ImportFactureFournisseurController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_import_facture_index',
        methods: ['GET']
    )]
    public function index(
        Exercice $exercice,
        ControleImportFactureFournisseurService $controleService,
    ): Response {

        $controle = $controleService->controler(
            $exercice
        );

        return $this->render(
            'import/facture/index.html.twig',
            [
                'exercice' => $exercice,
                'controle' => $controle,
            ]
        );
    }

    #[Route(
        '/{id}/importer',
        name: 'app_import_facture_importer',
        methods: ['POST']
    )]
    public function importer(
        Exercice $exercice,
        ImportFactureFournisseurService $importService,
    ): Response {

        try {

            $importService->importer($exercice);

            $this->addFlash(
                'success',
                'Les factures fournisseur ont été importées.'
            );
        } catch (\Throwable $e) {

            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_import_facture_index',
            [
                'id' => $exercice->getId(),
            ]
        );
    }
}
