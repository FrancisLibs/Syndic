<?php

namespace App\Controller;

use App\Entity\FactureFournisseur;
use App\Form\TraitementFnpType;
use App\Repository\FactureFournisseurRepository;
use App\Service\ContexteExerciceService;
use App\Service\TraitementFnpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;




#[Route('/fnp')]
class FnpController extends AbstractController
{
    #[Route(
        '/a-traiter',
        name: 'app_fnp_a_traiter',
        methods: ['GET']
    )]
    public function aTraiter(
        ContexteExerciceService $contexteExerciceService,
        FactureFournisseurRepository $factureFournisseurRepository,
    ): Response {
        $exercice =
            $contexteExerciceService->getExercice();

        $fnp =
            $factureFournisseurRepository
            ->findFnpATraiter($exercice);

        return $this->render(
            'fnp/a_traiter.html.twig',
            [
                'exercice' => $exercice,
                'fnp' => $fnp,
            ]
        );
    }

    #[Route(
        '/{id}/traiter',
        name: 'app_fnp_traiter',
        methods: ['GET', 'POST']
    )]
    public function traiter(
        FactureFournisseur $facture,
        Request $request,
        ContexteExerciceService $contexteExerciceService,
        TraitementFnpService $traitementFnpService,
    ): Response {
        $exercice =
            $contexteExerciceService->getExercice();

        /**
         * Sécurité : cette facture doit réellement être une FNP.
         */
        if (!$facture->isFnp()) {
            throw $this->createNotFoundException(
                'Cette facture n’est pas une FNP.'
            );
        }

        /**
         * Une FNP déjà traitée ne peut pas l'être une seconde fois.
         */
        if ($facture->isFnpTraitee()) {
            throw $this->createAccessDeniedException(
                'Cette FNP a déjà été traitée.'
            );
        }

        $form = $this->createForm(
            TraitementFnpType::class
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $dateFacture =
                $form->get('dateFacture')->getData();

            /**
             * La facture reçue doit appartenir
             * à l'exercice actuellement sélectionné.
             */
            if (
                $dateFacture < $exercice->getDateDebut()
                || $dateFacture > $exercice->getDateFin()
            ) {
                $this->addFlash(
                    'danger',
                    'La date de la facture doit appartenir à l’exercice '
                        . $exercice->getNom()
                        . '.'
                );
            } else {
                /**
                 * Traitement comptable de la FNP.
                 */
                $traitementFnpService->traiter(
                    $facture,
                    $exercice,
                    $dateFacture,
                    $form->get('numero')->getData(),
                    (bool) $form->get('imputerAcompte')->getData()
                );

                $this->addFlash(
                    'success',
                    'La FNP a été traitée.'
                );

                return $this->redirectToRoute(
                    'app_fnp_a_traiter'
                );
            }
        }

        return $this->render(
            'fnp/traiter.html.twig',
            [
                'facture' => $facture,
                'exercice' => $exercice,
                'form' => $form,
            ]
        );
    }
}
