<?php

namespace App\Controller;

use App\Entity\ReglementFournisseur;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ReglementFournisseurRepository;
use App\Form\ReglementFournisseurType;
use App\Service\ReglementFournisseurService;
use App\Service\ContexteExerciceService;
use App\Service\CorrectionReglementFournisseurService;
use App\Service\ModificationReglementFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

#[Route('/reglements-fournisseurs')]
final class ReglementFournisseurController extends AbstractController
{
    #[Route(
        '/',
        name: 'app_reglement_fournisseur_index',
        methods: ['GET']
    )]
    public function index(
        ReglementFournisseurRepository $repository,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice =
            $contexteExerciceService->getExercice();

        if ($exercice === null) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $reglements =
            $repository->findPourExercice(
                $exercice
            );

        return $this->render(
            'reglement_fournisseur/index.html.twig',
            [
                'exercice' => $exercice,
                'reglements' => $reglements,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_reglement_fournisseur_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        ReglementFournisseur $reglement,
    ): Response {
        return $this->render(
            'reglement_fournisseur/show.html.twig',
            [
                'reglement' => $reglement,
            ]
        );
    }

    #[Route(
        '/nouveau',
        name: 'app_reglement_fournisseur_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        FournisseurRepository $fournisseurRepository,
        ExerciceRepository $exerciceRepository,
        ReglementFournisseurService $reglementService,
    ): Response {
        $exercice = $exerciceRepository->findActif();

        if ($exercice === null) {
            throw new \LogicException(
                'Aucun exercice actif trouvé.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’enregistrer un règlement sur un exercice clôturé.'
            );
        }

        $fournisseurs = $fournisseurRepository
            ->findBy(
                [],
                ['nom' => 'ASC']
            );

        /*
     * Fournisseur sélectionné.
     */
        $fournisseurId = $request->isMethod('POST')
            ? (int) $request->request->get('fournisseur_id')
            : (int) $request->query->get('fournisseur');

        $fournisseur = null;

        if ($fournisseurId > 0) {

            $fournisseur =
                $fournisseurRepository->find(
                    $fournisseurId
                );

            if ($fournisseur === null) {
                throw $this->createNotFoundException(
                    'Fournisseur introuvable.'
                );
            }
        }

        /*
     * Enregistrement.
     */
        if ($request->isMethod('POST')) {

            $token = (string) $request->request
                ->get('_token');

            if (
                !$this->isCsrfTokenValid(
                    'reglement-fournisseur',
                    $token
                )
            ) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            if ($fournisseur === null) {
                $this->addFlash(
                    'danger',
                    'Aucun fournisseur sélectionné.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new'
                );
            }

            /*
         * Date.
         */
            $dateTexte = trim(
                (string) $request->request
                    ->get('date_reglement')
            );

            $dateReglement =
                \DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $dateTexte
                );

            if ($dateReglement === false) {
                $this->addFlash(
                    'danger',
                    'La date de règlement est invalide.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new',
                    [
                        'fournisseur' =>
                        $fournisseur->getId(),
                    ]
                );
            }

            /*
         * Montant.
         */
            $montantTexte = trim(
                (string) $request->request
                    ->get('montant')
            );

            $montantTexte = str_replace(
                ',',
                '.',
                $montantTexte
            );

            if (
                $montantTexte === ''
                || !is_numeric($montantTexte)
                || (float) $montantTexte <= 0
            ) {
                $this->addFlash(
                    'danger',
                    'Le montant du règlement est invalide.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new',
                    [
                        'fournisseur' =>
                        $fournisseur->getId(),
                    ]
                );
            }

            $montant = number_format(
                (float) $montantTexte,
                2,
                '.',
                ''
            );

            /*
         * Référence.
         */
            $reference = trim(
                (string) $request->request
                    ->get('reference')
            );

            try {

                $reglementService->enregistrer(
                    fournisseur: $fournisseur,
                    exercice: $exercice,
                    dateReglement: $dateReglement,
                    montant: $montant,
                    reference: $reference !== ''
                        ? $reference
                        : null,
                );

                $this->addFlash(
                    'success',
                    'Le règlement fournisseur a été enregistré.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new',
                    [
                        'fournisseur' =>
                        $fournisseur->getId(),
                    ]
                );
            } catch (\Throwable $exception) {

                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }
        }

        return $this->render(
            'reglement_fournisseur/new.html.twig',
            [
                'exercice' => $exercice,
                'fournisseurs' => $fournisseurs,
                'fournisseur' => $fournisseur,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_reglement_fournisseur_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        int $id,
        Request $request,
        ReglementFournisseurRepository $repository,
        ModificationReglementFournisseurService $modificationService,
    ): Response {

        $reglement = $repository->findAvecOperationPourCorrection($id);

        if ($reglement === null) {
            throw $this->createNotFoundException(
                'Règlement fournisseur introuvable.'
            );
        }

        if ($reglement->getExercice()?->isCloture()) {
            $this->addFlash(
                'danger',
                'Impossible de modifier un règlement d’un exercice clôturé.'
            );

            return $this->redirectToRoute(
                'app_reglement_fournisseur_show',
                [
                    'id' => $reglement->getId(),
                ]
            );
        }

        $form = $this->createForm(
            ReglementFournisseurType::class,
            $reglement
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            try {

                $modificationService->modifier(
                    $reglement
                );

                $this->addFlash(
                    'success',
                    'Le règlement fournisseur a été modifié.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_show',
                    [
                        'id' => $reglement->getId(),
                    ]
                );
            } catch (\Throwable $exception) {

                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }
        }

        return $this->render(
            'reglement_fournisseur/edit.html.twig',
            [
                'reglement' => $reglement,
                'form' => $form,
            ]
        );
    }
}
