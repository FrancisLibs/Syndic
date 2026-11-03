<?php

namespace App\Controller;

use App\Entity\AppelFond;
use App\Repository\AppelFondRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Service\AvisAppelFondService;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/appel/fond')]
final class AppelFondController extends AbstractController
{
    #[Route(name: 'app_appel_fond_index', methods: ['GET'])]
    public function index(
        AppelFondRepository $appelFondRepository
    ): Response {
        return $this->render(
            'appel_fond/index.html.twig',
            [
                'appel_fonds' => $appelFondRepository->findAll(),
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_appel_fond_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(AppelFond $appelFond): Response
    {
        return $this->render(
            'appel_fond/show.html.twig',
            [
                'appel_fond' => $appelFond,
            ]
        );
    }

    #[Route(
        '/avis/pdf',
        name: 'app_appel_fond_avis_pdf',
        methods: ['POST']
    )]
    public function genererAvisPdf(
        Request $request,
        AvisAppelFondService $avisAppelFondService
    ): Response {

        // Vérification CSRF
        if (!$this->isCsrfTokenValid(
            'generer_avis_appels',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        // Récupération des appels sélectionnés
        $appelIds = $request->request->all('appel_ids');

        if ($appelIds === []) {
            $this->addFlash(
                'warning',
                'Sélectionne au moins un appel de fonds.'
            );

            return $this->redirectToRoute('app_appel_fond_index');
        }

        $appelIds = array_values(array_unique(array_map(
            'intval',
            $appelIds
        )));

        // Préparation des avis individuels
        $avis = $avisAppelFondService->preparerAvis($appelIds);

        if ($avis === []) {
            $this->addFlash(
                'warning',
                'Aucun avis individuel à générer.'
            );

            return $this->redirectToRoute('app_appel_fond_index');
        }

        // Création de l'archive ZIP temporaire
        $zipPath = tempnam(sys_get_temp_dir(), 'avis_appels_');

        if ($zipPath === false) {
            throw new \RuntimeException(
                'Impossible de créer le fichier temporaire.'
            );
        }

        $zip = new \ZipArchive();

        if ($zip->open(
            $zipPath,
            \ZipArchive::CREATE | \ZipArchive::OVERWRITE
        ) !== true) {
            unlink($zipPath);

            throw new \RuntimeException(
                'Impossible de créer l’archive ZIP.'
            );
        }

        try {

            $cssPath = $this->getParameter('kernel.project_dir')
                . '/public/assets/css/pdf.css';

            $css = file_get_contents($cssPath);

            if ($css === false) {
                throw new \RuntimeException(
                    'Impossible de charger le fichier CSS des PDF.'
                );
            }

            foreach ($avis as $donnees) {

                $coproprietaire = $donnees['coproprietaire'];

                $html = $this->renderView(
                    'appel_fond/pdf/avis_individuel.html.twig',
                    [
                        'avis' => $donnees,
                        'css' => $css,
                    ]
                );

                // Génération du PDF
                $options = new Options();
                $options->set('defaultFont', 'DejaVu Sans');

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Nom du fichier PDF
                $nom = preg_replace(
                    '/[^a-zA-Z0-9_-]/',
                    '_',
                    $coproprietaire->getNomComplet()
                );

                $nomFichier = sprintf(
                    'Avis_%d_%s.pdf',
                    $coproprietaire->getId(),
                    $nom
                );

                if (!$zip->addFromString(
                    $nomFichier,
                    $dompdf->output()
                )) {
                    throw new \RuntimeException(
                        sprintf(
                            'Impossible d’ajouter le fichier %s au ZIP.',
                            $nomFichier
                        )
                    );
                }
            }
        } finally {
            $zip->close();
        }

        // Téléchargement du ZIP
        $response = new BinaryFileResponse($zipPath);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Avis_appels_de_fonds.zip'
        );

        $response->deleteFileAfterSend(true);

        return $response;
    }
}
