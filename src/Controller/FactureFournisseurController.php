<?php

namespace App\Controller;

use App\Entity\FactureFournisseur;
use App\Form\FactureFournisseurType;
use App\Form\CorrectionMontantFactureFournisseurType;
use App\Repository\ExerciceRepository;
use App\Repository\FactureFournisseurRepository;
use App\Repository\ReglementFournisseurRepository;
use App\Service\GenerationFactureFournisseurService;
use App\Service\ContexteExerciceService;
use App\Service\CorrectionFactureFournisseurService;
use App\Service\Travaux\CoutReelChantierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ReglementFournisseurService;


#[Route('/facture/fournisseur')]
final class FactureFournisseurController extends AbstractController
{
    #[Route(
        '/index',
        name: 'app_facture_fournisseur_index',
        methods: ['GET']
    )]
    public function index(
        FactureFournisseurRepository $repository,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $factures = $repository
            ->findPourExercice(
                $exercice
            );

        return $this->render(
            'facture_fournisseur/index.html.twig',
            [
                'factures' => $factures,
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/new',
        name: 'app_facture_fournisseur_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        GenerationFactureFournisseurService $generationService,
        ExerciceRepository $exerciceRepository,
        FactureFournisseurRepository $factureFournisseurRepository,
        CoutReelChantierService $coutReelChantierService,
    ): Response {
        $exercice = $exerciceRepository->findActif();

        if (!$exercice) {
            $this->addFlash(
                'danger',
                'Aucun exercice actif. La création d’une facture est impossible.'
            );

            return $this->redirectToRoute(
                'app_facture_fournisseur_index'
            );
        }

        if ($exercice->isCloture()) {
            $this->addFlash(
                'danger',
                'Impossible d’enregistrer une facture sur un exercice clôturé.'
            );

            return $this->redirectToRoute(
                'app_facture_fournisseur_index'
            );
        }

        $facture = new FactureFournisseur();

        $facture
            ->setExercice($exercice)
            ->setDateFacture(new \DateTimeImmutable());

        $form = $this->createForm(
            FactureFournisseurType::class,
            $facture,
            [
                'confirmation_correspondance' => $request->isMethod('POST'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $facture->setExercice($exercice);

            $doublon = $factureFournisseurRepository->findDoublon(
                $facture->getFournisseur(),
                $facture->getNumero(),
                $facture->getMontant()
            );

            if ($doublon !== null) {

                if ($doublon->isFnp() && !$doublon->isFnpTraitee()) {

                    $this->addFlash(
                        'warning',
                        sprintf(
                            'Cette facture correspond à une FNP enregistrée '
                                . 'dans l’exercice %s. '
                                . 'Traitez cette FNP plutôt que de créer une nouvelle facture.',
                            $doublon->getExercice()->getNom()
                        )
                    );

                    return $this->redirectToRoute(
                        'app_fnp_traiter',
                        [
                            'id' => $doublon->getId(),
                        ]
                    );
                }

                if ($doublon->isFnp() && $doublon->isFnpTraitee()) {

                    $this->addFlash(
                        'warning',
                        sprintf(
                            'Cette facture correspond à une FNP déjà traitée '
                                . 'dans l’exercice %s. '
                                . 'Aucune nouvelle facture n’a été créée.',
                            $doublon->getExercice()->getNom()
                        )
                    );

                    return $this->redirectToRoute(
                        'app_facture_fournisseur_show',
                        [
                            'id' => $doublon->getId(),
                        ]
                    );
                }

                $this->addFlash(
                    'warning',
                    sprintf(
                        'Cette facture semble déjà avoir été enregistrée '
                            . 'dans l’exercice %s. '
                            . 'Aucune nouvelle facture n’a été créée.',
                        $doublon->getExercice()->getNom()
                    )
                );

                return $this->redirectToRoute(
                    'app_facture_fournisseur_show',
                    [
                        'id' => $doublon->getId(),
                    ]
                );
            }

            $correspondancePossible =
                $factureFournisseurRepository->findCorrespondancePossible(
                    $facture->getFournisseur(),
                    $facture->getMontant()
                );

            if (
                $correspondancePossible !== null
                && !$form->get('confirmerCorrespondance')->getData()
            ) {
                $this->addFlash(
                    'warning',
                    sprintf(
                        'Attention : une autre facture du fournisseur %s '
                            . 'd’un montant de %s € existe déjà '
                            . 'dans l’exercice %s. Vérifiez qu’il ne s’agit pas '
                            . 'de la même facture.',
                        $correspondancePossible->getFournisseur()->getNom(),
                        number_format(
                            $correspondancePossible->getMontantFacture(),
                            2,
                            ',',
                            ' '
                        ),
                        $correspondancePossible->getExercice()->getNom()
                    )
                );

                return $this->render(
                    'facture_fournisseur/new.html.twig',
                    [
                        'exercice' => $exercice,
                        'facture' => $facture,
                        'form' => $form,
                    ]
                );
            }

            // Contrôle du montant autorisé pour les travaux
            $chantier = $facture->getChantier();

            if ($chantier !== null) {

                if ($chantier->isCloture()) {
                    $this->addFlash(
                        'danger',
                        'Impossible d’enregistrer une facture sur un chantier clôturé.'
                    );

                    return $this->render(
                        'facture_fournisseur/new.html.twig',
                        [
                            'exercice' => $exercice,
                            'facture' => $facture,
                            'form' => $form,
                        ]
                    );
                }

                $montantAutorise = $chantier->getMontantTotalVote();

                $coutActuel = $coutReelChantierService->calculer($chantier);

                $nouveauCout = round(
                    $coutActuel + $facture->getMontantFacture(),
                    2
                );

                if ($nouveauCout > $montantAutorise + 0.009) {

                    $depassement = $nouveauCout - $montantAutorise;

                    $this->addFlash(
                        'danger',
                        sprintf(
                            'Impossible d’enregistrer cette facture : '
                                . 'le coût du chantier atteindrait %s €, '
                                . 'alors que le montant total voté est de %s €. '
                                . 'Dépassement : %s €. '
                                . 'Un vote complémentaire est nécessaire.',
                            number_format($nouveauCout, 2, ',', ' '),
                            number_format($montantAutorise, 2, ',', ' '),
                            number_format($depassement, 2, ',', ' ')
                        )
                    );

                    return $this->render(
                        'facture_fournisseur/new.html.twig',
                        [
                            'exercice' => $exercice,
                            'facture' => $facture,
                            'form' => $form,
                        ]
                    );
                }
            }

            try {
                $entityManager->wrapInTransaction(
                    function () use (
                        $entityManager,
                        $generationService,
                        $facture
                    ): void {
                        $entityManager->persist($facture);

                        $generationService->generer($facture);
                    }
                );

                $this->addFlash(
                    'success',
                    'La facture fournisseur a été enregistrée et comptabilisée.'
                );

                return $this->redirectToRoute(
                    'app_facture_fournisseur_show',
                    [
                        'id' => $facture->getId(),
                    ]
                );
            } catch (\Throwable $exception) {
                $this->addFlash(
                    'danger',
                    'La facture n’a pas pu être comptabilisée : '
                        . $exception->getMessage()
                );
            }
        }

        return $this->render(
            'facture_fournisseur/new.html.twig',
            [
                'exercice' => $exercice,
                'facture' => $facture,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_facture_fournisseur_show',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function show(
        FactureFournisseur $factureFournisseur,
        ReglementFournisseurRepository $reglementRepository,
    ): Response {

        $reglements = [];

        if (
            $factureFournisseur->isComptabilisee()
            && $factureFournisseur->estFacture()
            && !$factureFournisseur->isFnp()
            && $factureFournisseur->getCoproprietaireAvanceur() === null
            && !$factureFournisseur->getExercice()->isCloture()
        ) {

            $candidats = $reglementRepository->findBy(
                [
                    'fournisseur' => $factureFournisseur->getFournisseur(),
                    'exercice' => $factureFournisseur->getExercice(),
                ],
                [
                    'dateReglement' => 'ASC',
                ]
            );

            foreach ($candidats as $reglement) {

                if ($reglement->getMontantNonAffecte() <= 0) {
                    continue;
                }

                $reglements[] = $reglement;
            }
        }

        return $this->render(
            'facture_fournisseur/show.html.twig',
            [
                'facture_fournisseur' => $factureFournisseur,
                'reglements' => $reglements,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_facture_fournisseur_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+']
    )]
    public function edit(
        Request $request,
        FactureFournisseur $facture,
        EntityManagerInterface $entityManager,
        GenerationFactureFournisseurService $generationService,
    ): Response {
        if ($facture->isComptabilisee()) {

            $this->addFlash(
                'warning',
                'Une facture comptabilisée ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_facture_fournisseur_show',
                [
                    'id' => $facture->getId(),
                ]
            );
        }

        $form = $this->createForm(
            FactureFournisseurType::class,
            $facture
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            try {
                $entityManager->wrapInTransaction(
                    function () use (
                        $entityManager,
                        $generationService,
                        $facture
                    ): void {
                        $generationService->generer($facture);
                    }
                );

                $this->addFlash(
                    'success',
                    'La facture a été modifiée et comptabilisée.'
                );

                return $this->redirectToRoute(
                    'app_facture_fournisseur_show',
                    [
                        'id' => $facture->getId(),
                    ]
                );
            } catch (\Throwable $exception) {

                $this->addFlash(
                    'danger',
                    'La facture n’a pas pu être comptabilisée : '
                        . $exception->getMessage()
                );
            }
        }

        return $this->render(
            'facture_fournisseur/edit.html.twig',
            [
                'facture' => $facture,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_facture_fournisseur_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function delete(
        Request $request,
        FactureFournisseur $facture,
        EntityManagerInterface $entityManager
    ): Response {

        if ($facture->isComptabilisee()) {

            throw $this->createAccessDeniedException(
                'Facture comptabilisée'
            );
        }

        if (
            $this->isCsrfTokenValid(
                'delete' . $facture->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {

            $entityManager->remove($facture);

            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_facture_fournisseur_index'
        );
    }

    #[Route(
        '/{id}/corriger-montant',
        name: 'app_facture_fournisseur_corriger_montant',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function corrigerMontant(
        int $id,
        Request $request,
        FactureFournisseurRepository $factureRepository,
        CorrectionFactureFournisseurService $correctionService
    ): Response {
        $facture = $factureRepository
            ->findAvecOperationPourCorrection($id);
        if ($facture === null) {
            throw $this->createNotFoundException(
                'Facture fournisseur introuvable.'
            );
        }

        if ($facture->getCoproprietaireAvanceur() !== null) {
            $this->addFlash(
                'warning',
                'La correction des factures avancées par un copropriétaire n’est pas encore disponible.'
            );

            return $this->redirectToRoute(
                'app_facture_fournisseur_show',
                [
                    'id' => $facture->getId(),
                ]
            );
        }

        $ancienMontant = $facture->getMontant();

        $form = $this->createForm(
            CorrectionMontantFactureFournisseurType::class,
            [
                'montant' => $ancienMontant,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            try {
                $correctionService->corrigerMontant(
                    $facture,
                    (string) $data['montant']
                );

                $this->addFlash(
                    'success',
                    sprintf(
                        'Le montant de la facture a été corrigé de %.2f € à %.2f €.',
                        (float) $ancienMontant,
                        (float) $data['montant']
                    )
                );

                return $this->redirectToRoute(
                    'app_facture_fournisseur_show',
                    [
                        'id' => $facture->getId(),
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
            'facture_fournisseur/corriger_montant.html.twig',
            [
                'facture' => $facture,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/affecter-reglements',
        name: 'app_facture_fournisseur_affecter_reglements',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function affecterReglements(
        FactureFournisseur $factureFournisseur,
        Request $request,
        ReglementFournisseurRepository $reglementRepository,
        ReglementFournisseurService $reglementService,
        EntityManagerInterface $entityManager,
    ): Response {

        $retour = [
            'id' => $factureFournisseur->getId(),
        ];

        if (!$this->isCsrfTokenValid(
            'affecter-reglements-facture-' . $factureFournisseur->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        try {

            if ($factureFournisseur->getExercice()->isCloture()) {
                throw new \LogicException(
                    'Impossible de modifier les affectations d’un exercice clôturé.'
                );
            }

            if (
                !$factureFournisseur->isComptabilisee()
                || !$factureFournisseur->estFacture()
                || $factureFournisseur->isFnp()
                || $factureFournisseur->getCoproprietaireAvanceur() !== null
            ) {
                throw new \LogicException(
                    'Cette facture ne peut pas recevoir de règlement fournisseur.'
                );
            }

            $ids = $request->request->all('reglements');

            if ($ids === []) {
                throw new \LogicException(
                    'Aucun règlement sélectionné.'
                );
            }

            $entityManager->wrapInTransaction(
                function () use (
                    $ids,
                    $factureFournisseur,
                    $reglementRepository,
                    $reglementService
                ): void {

                    foreach (array_unique($ids) as $id) {

                        if (!is_scalar($id) || !ctype_digit((string) $id)) {
                            throw new \LogicException(
                                'Identifiant de règlement invalide.'
                            );
                        }

                        $reglement = $reglementRepository->find((int) $id);

                        if ($reglement === null) {
                            throw new \LogicException(
                                'Règlement introuvable.'
                            );
                        }

                        if (
                            $reglement->getFournisseur() !== $factureFournisseur->getFournisseur()
                            || $reglement->getExercice() !== $factureFournisseur->getExercice()
                        ) {
                            throw new \LogicException(
                                'Ce règlement ne correspond pas à la facture.'
                            );
                        }

                        $disponible = (int) round(
                            $reglement->getMontantNonAffecte() * 100
                        );

                        $reste = (int) round(
                            $factureFournisseur->getResteARegler() * 100
                        );

                        $montantCentimes = min($disponible, $reste);

                        if ($montantCentimes <= 0) {
                            break;
                        }

                        $reglementService->affecterFacture(
                            $reglement,
                            $factureFournisseur,
                            number_format(
                                $montantCentimes / 100,
                                2,
                                '.',
                                ''
                            )
                        );
                    }
                }
            );

            $this->addFlash(
                'success',
                'Les règlements ont été affectés à la facture.'
            );
        } catch (\LogicException $exception) {

            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_facture_fournisseur_show',
            $retour
        );
    }
}
