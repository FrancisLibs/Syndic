<?php

namespace App\Controller\Imports;

use App\Entity\Exercice;
use App\Service\Imports\ControleImportFactureFournisseurService;
use App\Service\Imports\ImportFactureFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Imports\ImportFactureFournisseur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/imports/factures')]
final class ImportFactureFournisseurController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_import_facture_index',
        requirements: ['id' => '\d+'],
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
            'imports/facture/index.html.twig',
            [
                'exercice' => $exercice,
                'controle' => $controle,
            ]
        );
    }

    #[Route(
        '/{id}/importer',
        name: 'app_import_facture_importer',
        requirements: ['id' => '\d+'],
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

    #[Route(
        '/{id}/ligne/{importId}/supprimer',
        name: 'app_import_facture_supprimer',
        requirements: [
            'id' => '\d+',
            'importId' => '\d+',
        ],
        methods: ['POST']
    )]
    public function supprimer(
        Exercice $exercice,
        int $importId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $import = $entityManager
            ->getRepository(ImportFactureFournisseur::class)
            ->find($importId);

        if (!$import) {
            throw $this->createNotFoundException(
                'La ligne d\'import n\'existe pas.'
            );
        }

        /*
     * Sécurité :
     * la ligne doit appartenir à l'exercice affiché.
     */
        if ($import->getExercice() !== $exercice) {
            throw $this->createNotFoundException(
                'Cette ligne d\'import n\'appartient pas à cet exercice.'
            );
        }

        /*
     * On ne supprime jamais une ligne ayant déjà
     * généré une facture fournisseur.
     */
        if ($import->getFactureCreee() !== null) {
            $this->addFlash(
                'danger',
                'Cette ligne ne peut pas être supprimée car elle a déjà généré une facture.'
            );

            return $this->redirectToRoute(
                'app_import_facture_index',
                [
                    'id' => $exercice->getId(),
                ]
            );
        }

        if (!$this->isCsrfTokenValid(
            'supprimer_import_facture_' . $import->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $entityManager->remove($import);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La ligne d\'import a été supprimée.'
        );

        return $this->redirectToRoute(
            'app_import_facture_index',
            [
                'id' => $exercice->getId(),
            ]
        );
    }
}
