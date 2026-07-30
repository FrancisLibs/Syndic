<?php

namespace App\Controller;

use App\Form\Imports\ImportCsvFactureFournisseurType;
use App\Service\Imports\AnalyseImportFactureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/initialisation')]
final class InitialisationController extends AbstractController
{
    #[Route(
        '/import-factures',
        name: 'app_initialisation_import_factures'
    )]
    public function importFactures(
        Request $request,
        AnalyseImportFactureService $analyseService,
        SessionInterface $session
    ): Response {

        $form = $this->createForm(
            ImportCsvFactureFournisseurType::class
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {

            /** @var UploadedFile $fichier */
            $fichier = $form
                ->get('fichier')
                ->getData();

            try {

                $rapport =
                    $analyseService->analyser(
                        $fichier
                    );

                $session->set(
                    'rapport_import_factures',
                    serialize($rapport)
                );

                return $this->render(
                    'initialisation/apercu_import_factures.html.twig',
                    [
                        'rapport' => $rapport,
                    ]
                );
            } catch (\Throwable $e) {

                $this->addFlash(
                    'danger',
                    $e->getMessage()
                );
            }
        }

        return $this->render(
            'initialisation/import_factures.html.twig',
            [
                'form' => $form,
            ]
        );
    }
}
