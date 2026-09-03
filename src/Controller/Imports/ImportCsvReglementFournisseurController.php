<?php

namespace App\Controller\Imports;

use App\Form\Imports\ImportCsvReglementFournisseurType;
use App\Service\Imports\AnalyseImportReglementService;
use App\Service\Imports\ImportCsvReglementFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/imports/reglements/csv')]
final class ImportCsvReglementFournisseurController extends AbstractController
{
    #[Route(
        '',
        name: 'app_import_csv_reglement',
        methods: ['GET', 'POST']
    )]
    public function index(
        Request $request,
        AnalyseImportReglementService $analyseService,
    ): Response {
        $form = $this->createForm(
            ImportCsvReglementFournisseurType::class
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            /** @var UploadedFile|null $fichier */
            $fichier = $form
                ->get('fichier')
                ->getData();

            if (!$fichier instanceof UploadedFile) {
                $this->addFlash(
                    'danger',
                    'Aucun fichier CSV n’a été sélectionné.'
                );

                return $this->redirectToRoute(
                    'app_import_csv_reglement'
                );
            }

            $repertoire = sprintf(
                '%s/var/import',
                $this->getParameter('kernel.project_dir')
            );

            if (
                !is_dir($repertoire)
                && !mkdir($repertoire, 0777, true)
                && !is_dir($repertoire)
            ) {
                throw new \RuntimeException(
                    'Impossible de créer le répertoire d’import.'
                );
            }

            $nomFichier = sprintf(
                'reglements_%s_%s.csv',
                date('Ymd_His'),
                bin2hex(random_bytes(4))
            );

            $fichier->move(
                $repertoire,
                $nomFichier
            );

            $chemin =
                $repertoire . '/' . $nomFichier;

            try {
                $rapport = $analyseService->analyser(
                    $chemin
                );
            } catch (\Throwable $e) {
                if (is_file($chemin)) {
                    unlink($chemin);
                }

                $this->addFlash(
                    'danger',
                    $e->getMessage()
                );

                return $this->redirectToRoute(
                    'app_import_csv_reglement'
                );
            }

            return $this->render(
                'imports/reglement/apercu.html.twig',
                [
                    'rapport' => $rapport,
                    'nomFichier' => $nomFichier,
                ]
            );
        }

        return $this->render(
            'imports/reglement/import_csv.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/importer',
        name: 'app_import_csv_reglement_importer',
        methods: ['POST']
    )]
    public function importer(
        Request $request,
        AnalyseImportReglementService $analyseService,
        ImportCsvReglementFournisseurService $importService,
    ): Response {
        if (
            !$this->isCsrfTokenValid(
                'import_csv_reglements',
                (string) $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $nomFichier = basename(
            (string) $request->request->get('nomFichier')
        );

        if ($nomFichier === '') {
            $this->addFlash(
                'danger',
                'Aucun fichier n’est disponible pour l’import.'
            );

            return $this->redirectToRoute(
                'app_import_csv_reglement'
            );
        }

        $chemin = sprintf(
            '%s/var/import/%s',
            $this->getParameter('kernel.project_dir'),
            $nomFichier
        );

        if (!is_file($chemin)) {
            $this->addFlash(
                'danger',
                'Le fichier CSV n’existe plus.'
            );

            return $this->redirectToRoute(
                'app_import_csv_reglement'
            );
        }

        try {
            $rapport = $analyseService->analyser(
                $chemin
            );

            if (!$rapport->estValide()) {
                $this->addFlash(
                    'danger',
                    'Le fichier contient encore des erreurs. Aucun import n’a été effectué.'
                );

                return $this->render(
                    'imports/reglement/apercu.html.twig',
                    [
                        'rapport' => $rapport,
                        'nomFichier' => $nomFichier,
                    ]
                );
            }

            $nombreImporte =
                $importService->importer(
                    $rapport
                );

            unlink($chemin);

            $this->addFlash(
                'success',
                sprintf(
                    '%d ligne(s) ont été ajoutée(s) à la table d’import des règlements.',
                    $nombreImporte
                )
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_import_csv_reglement'
        );
    }
}
