<?php

namespace App\Controller;

use App\Repository\FactureFournisseurRepository;
use App\Repository\ExerciceRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ReglementFournisseurRepository;
use App\Repository\EcritureRepository;
use App\Entity\ReglementFournisseur;
use App\Entity\AffectationReglementFournisseur;
use App\Form\ReglementFournisseurType;
use App\Service\ReglementFournisseurService;
use App\Service\ContexteExerciceService;
use App\Service\ModificationReglementFournisseurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

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

        $reglements = $repository->findPourExercice(
            $exercice
        );

        $groupes = [];
        $totalGeneral = 0.0;

        foreach ($reglements as $reglement) {

            $fournisseur = $reglement->getFournisseur();

            if ($fournisseur === null) {
                continue;
            }

            $id = $fournisseur->getId();

            if (!isset($groupes[$id])) {
                $groupes[$id] = [
                    'fournisseur' => $fournisseur,
                    'reglements' => [],
                    'total' => 0.0,
                ];
            }

            $montant = (float) $reglement->getMontant();

            $groupes[$id]['reglements'][] = $reglement;
            $groupes[$id]['total'] += $montant;

            $totalGeneral += $montant;
        }

        /*
 * Classement alphabétique des fournisseurs.
 */
        uasort(
            $groupes,
            static fn(array $a, array $b): int =>
            strcasecmp(
                $a['fournisseur']->getNom(),
                $b['fournisseur']->getNom()
            )
        );

        return $this->render(
            'reglement_fournisseur/index.html.twig',
            [
                'exercice' => $exercice,
                'reglements' => $reglements,
                'groupes' => $groupes,
                'totalGeneral' => $totalGeneral,
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
        FactureFournisseurRepository $factureRepository,
    ): Response {

        $facturesCandidates = $factureRepository->findBy(
            [
                'fournisseur' => $reglement->getFournisseur(),
                'exercice' => $reglement->getExercice(),
                'comptabilisee' => true,
                'fnp' => false,
                'coproprietaireAvanceur' => null,
            ],
            [
                'dateFacture' => 'ASC',
            ]
        );

        $factures = [];

        foreach ($facturesCandidates as $facture) {

            if (
                !$facture->estFacture()
                || $facture->getResteARegler() <= 0
            ) {
                continue;
            }

            $factures[] = $facture;
        }

        return $this->render(
            'reglement_fournisseur/show.html.twig',
            [
                'reglement' => $reglement,
                'factures' => $factures,
            ]
        );
    }

    #[Route(
        '/{id}/affectations',
        name: 'app_reglement_fournisseur_affectations',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function enregistrerAffectations(
        ReglementFournisseur $reglement,
        Request $request,
        FactureFournisseurRepository $factureRepository,
        ReglementFournisseurService $reglementService,
        EntityManagerInterface $entityManager,
    ): Response {

        if ($reglement->getExercice()->isCloture()) {
            $this->addFlash(
                'danger',
                'Impossible de modifier les affectations d’un exercice clôturé.'
            );

            return $this->redirectToRoute(
                'app_reglement_fournisseur_show',
                ['id' => $reglement->getId()]
            );
        }

        if (!$this->isCsrfTokenValid(
            'affectations-reglement-' . $reglement->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        try {
            $montantsSaisis = $request->request->all('affectations');

            $affectations = [];
            $totalCentimes = 0;

            foreach ($montantsSaisis as $factureId => $montantSaisi) {

                if (
                    !ctype_digit((string) $factureId)
                    || !is_scalar($montantSaisi)
                ) {
                    throw new \LogicException(
                        'Une affectation est invalide.'
                    );
                }

                $montantTexte = str_replace(
                    ',',
                    '.',
                    trim((string) $montantSaisi)
                );

                if (!preg_match(
                    '/^\d+(?:\.\d{1,2})?$/',
                    $montantTexte
                )) {
                    throw new \LogicException(
                        'Le montant d’une affectation est invalide.'
                    );
                }

                $montantCentimes = (int) round(
                    (float) $montantTexte * 100
                );

                if ($montantCentimes === 0) {
                    continue;
                }

                $facture = $factureRepository->find(
                    (int) $factureId
                );

                if ($facture === null) {
                    throw new \LogicException(
                        'Facture introuvable.'
                    );
                }

                if (
                    $facture->getFournisseur() !== $reglement->getFournisseur()
                    || $facture->getExercice() !== $reglement->getExercice()
                ) {
                    throw new \LogicException(
                        'Cette facture ne peut pas recevoir ce règlement.'
                    );
                }

                $totalCentimes += $montantCentimes;

                $affectations[] = [
                    'facture' => $facture,
                    'montant' => number_format(
                        $montantCentimes / 100,
                        2,
                        '.',
                        ''
                    ),
                ];
            }

            $disponibleCentimes = (int) round(
                $reglement->getMontantNonAffecte() * 100
            );

            if ($totalCentimes > $disponibleCentimes) {
                throw new \LogicException(
                    'Le total des affectations dépasse le montant disponible du règlement.'
                );
            }

            if ($affectations === []) {
                throw new \LogicException(
                    'Aucun montant à affecter.'
                );
            }

            $entityManager->wrapInTransaction(
                function () use (
                    $affectations,
                    $reglementService,
                    $reglement
                ): void {

                    foreach ($affectations as $ligne) {
                        $reglementService->affecterFacture(
                            $reglement,
                            $ligne['facture'],
                            $ligne['montant']
                        );
                    }
                }
            );

            $this->addFlash(
                'success',
                'Les affectations ont été enregistrées.'
            );
        } catch (\Throwable $exception) {

            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_reglement_fournisseur_show',
            ['id' => $reglement->getId()]
        );
    }


    #[Route(
        '/facture/{id}/payer',
        name: 'app_reglement_fournisseur_payer_facture',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function payerFacture(
        int $id,
        Request $request,
        FactureFournisseurRepository $factureRepository,
        ExerciceRepository $exerciceRepository,
        ReglementFournisseurService $reglementService,
    ): Response {

        $facture = $factureRepository->find($id);

        if ($facture === null) {
            throw $this->createNotFoundException(
                'Facture fournisseur introuvable.'
            );
        }

        $exercice = $exerciceRepository->findActif();

        if (
            $exercice === null
            || $exercice->isCloture()
            || $facture->getExercice()?->getId() !== $exercice->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Cette facture ne peut pas être réglée sur cet exercice.'
            );
        }

        if (
            !$facture->estFacture()
            || !$facture->isComptabilisee()
            || $facture->isFnp()
            || $facture->getCoproprietaireAvanceur() !== null
            || $facture->getMontantRegle() > 0
            || $facture->getResteARegler() <= 0
        ) {
            throw $this->createAccessDeniedException(
                'Cette facture ne peut pas être réglée directement.'
            );
        }

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'payer-facture-' . $facture->getId(),
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            $dateTexte = trim(
                (string) $request->request->get('date_reglement')
            );

            $dateReglement = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $dateTexte
            );

            if (
                $dateReglement === false
                || $dateReglement->format('Y-m-d') !== $dateTexte
            ) {
                $this->addFlash(
                    'danger',
                    'La date de règlement est invalide.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_payer_facture',
                    ['id' => $facture->getId()]
                );
            }

            $reference = trim(
                (string) $request->request->get('reference')
            );

            try {
                $reglement = $reglementService->payerFacture(
                    $facture,
                    $dateReglement,
                    $reference !== '' ? $reference : null
                );

                $this->addFlash(
                    'success',
                    'La facture a été réglée et affectée au paiement.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_show',
                    ['id' => $reglement->getId()]
                );
            } catch (\Throwable $exception) {
                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }
        }

        return $this->render(
            'reglement_fournisseur/payer_facture.html.twig',
            [
                'facture' => $facture,
                'exercice' => $exercice,
            ]
        );
    }


#[Route(
        '/facture/{id}/affecter',
        name: 'app_reglement_fournisseur_affecter_facture',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function affecterFactureExistante(
        int $id,
        Request $request,
        FactureFournisseurRepository $factureRepository,
        ReglementFournisseurRepository $reglementRepository,
        ExerciceRepository $exerciceRepository,
        ReglementFournisseurService $reglementService,
        EntityManagerInterface $entityManager,
    ): Response {

        $facture = $factureRepository->find($id);
        $exercice = $exerciceRepository->findActif();

        if ($facture === null) {
            throw $this->createNotFoundException(
                'Facture fournisseur introuvable.'
            );
        }

        if (
            $exercice === null
            || $exercice->isCloture()
            || $facture->getExercice()?->getId() !== $exercice->getId()
            || !$facture->estFacture()
            || !$facture->isComptabilisee()
            || $facture->isFnp()
            || $facture->getCoproprietaireAvanceur() !== null
            || $facture->getResteARegler() <= 0
        ) {
            throw $this->createAccessDeniedException(
                'Cette facture ne peut pas recevoir une affectation.'
            );
        }

        $reglements = $reglementRepository->findBy(
            [
                'fournisseur' => $facture->getFournisseur(),
                'exercice' => $exercice,
            ],
            ['dateReglement' => 'DESC']
        );

        $reglementsDisponibles = [];

        foreach ($reglements as $reglement) {
            if ($reglement->getMontantNonAffecte() > 0) {
                $reglementsDisponibles[] = $reglement;
            }
        }

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'affecter-facture-' . $facture->getId(),
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            try {
                $reglementId = $request->request->get('reglement_id');

                if (
                    !is_scalar($reglementId)
                    || !ctype_digit((string) $reglementId)
                ) {
                    throw new \LogicException(
                        'Veuillez sélectionner un règlement.'
                    );
                }

                $reglement = null;

                foreach ($reglementsDisponibles as $candidat) {
                    if ($candidat->getId() === (int) $reglementId) {
                        $reglement = $candidat;
                        break;
                    }
                }

                if ($reglement === null) {
                    throw new \LogicException(
                        'Ce règlement n’est pas disponible.'
                    );
                }

                // Affectation intégrale de la facture.
                $montant = number_format(
                    $facture->getResteARegler(),
                    2,
                    '.',
                    ''
                );

                $entityManager->wrapInTransaction(
                    function () use (
                        $reglementService,
                        $reglement,
                        $facture,
                        $montant
                    ): void {
                        $reglementService->affecterFacture(
                            $reglement,
                            $facture,
                            $montant
                        );
                    }
                );

                $this->addFlash(
                    'success',
                    'La facture a été associée au règlement existant.'
                );

                return $this->redirectToRoute(
                    'app_facture_fournisseur_index'
                );
            } catch (\LogicException $exception) {
                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }
        }

        return $this->render(
            'reglement_fournisseur/affecter_facture.html.twig',
            [
                'facture' => $facture,
                'reglements' => $reglementsDisponibles,
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
        FactureFournisseurRepository $factureRepository,
        EcritureRepository $ecritureRepository,
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

        $fournisseurs = $fournisseurRepository->findBy(
            [],
            ['nom' => 'ASC']
        );

        // Fournisseur sélectionné
        $fournisseurId = $request->isMethod('POST')
            ? (int) $request->request->get('fournisseur_id')
            : (int) $request->query->get('fournisseur');

        $fournisseur = null;

        if ($fournisseurId > 0) {
            $fournisseur = $fournisseurRepository->find($fournisseurId);

            if ($fournisseur === null) {
                throw $this->createNotFoundException(
                    'Fournisseur introuvable.'
                );
            }
        }

        // Solde comptable du fournisseur
        $soldeFournisseur = 0.0;

        if (
            $fournisseur !== null
            && $fournisseur->getCompte() !== null
        ) {
            $soldeFournisseur = round(
                -$ecritureRepository->calculerSoldeCompte(
                    $exercice,
                    $fournisseur->getCompte()
                ),
                2
            );
        }

        // Factures de l'exercice pouvant recevoir une affectation
        $factures = [];

        if ($fournisseur !== null) {
            $facturesCandidates = $factureRepository->findBy(
                [
                    'fournisseur' => $fournisseur,
                    'exercice' => $exercice,
                    'comptabilisee' => true,
                    'fnp' => false,
                    'coproprietaireAvanceur' => null,
                ],
                [
                    'dateFacture' => 'ASC',
                ]
            );

            foreach ($facturesCandidates as $facture) {
                if (
                    !$facture->estFacture()
                    || $facture->getResteARegler() <= 0
                ) {
                    continue;
                }

                $factures[] = $facture;
            }
        }

        // Enregistrement
        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');

            if (!$this->isCsrfTokenValid('reglement-fournisseur', $token)) {
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

            // Date
            $dateTexte = trim(
                (string) $request->request->get('date_reglement')
            );

            $dateReglement = \DateTimeImmutable::createFromFormat(
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
                    ['fournisseur' => $fournisseur->getId()]
                );
            }

            // Montant du règlement
            $montantTexte = str_replace(
                ',',
                '.',
                trim((string) $request->request->get('montant'))
            );

            if (
                !preg_match('/^\d+(?:\.\d{1,2})?$/', $montantTexte)
                || (float) $montantTexte <= 0
            ) {
                $this->addFlash(
                    'danger',
                    'Le montant du règlement est invalide.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new',
                    ['fournisseur' => $fournisseur->getId()]
                );
            }

            $montant = number_format(
                (float) $montantTexte,
                2,
                '.',
                ''
            );

            // Référence
            $reference = trim(
                (string) $request->request->get('reference')
            );

            try {
                // Montants saisis dans le tableau des factures
                $montantsSaisis = $request->request->all('affectations');

                // Factures autorisées pour ce fournisseur et cet exercice
                $facturesParId = [];

                foreach ($factures as $facture) {
                    $facturesParId[$facture->getId()] = $facture;
                }

                $affectations = [];
                $totalAffecteCentimes = 0;

                foreach ($montantsSaisis as $factureId => $montantSaisi) {
                    if (
                        !is_scalar($montantSaisi)
                        || !ctype_digit((string) $factureId)
                    ) {
                        throw new \LogicException(
                            'Une affectation est invalide.'
                        );
                    }

                    $montantAffectation = str_replace(
                        ',',
                        '.',
                        trim((string) $montantSaisi)
                    );

                    if (
                        !preg_match(
                            '/^\d+(?:\.\d{1,2})?$/',
                            $montantAffectation
                        )
                    ) {
                        throw new \LogicException(
                            'Le montant d’une affectation est invalide.'
                        );
                    }

                    $montantCentimes = (int) round(
                        (float) $montantAffectation * 100
                    );

                    if ($montantCentimes === 0) {
                        continue;
                    }

                    $factureId = (int) $factureId;

                    if (!isset($facturesParId[$factureId])) {
                        throw new \LogicException(
                            'La facture sélectionnée ne peut pas recevoir ce règlement.'
                        );
                    }

                    $facture = $facturesParId[$factureId];

                    $resteCentimes = (int) round(
                        $facture->getResteARegler() * 100
                    );

                    if ($montantCentimes > $resteCentimes) {
                        throw new \LogicException(
                            sprintf(
                                'Le montant affecté à la facture %s dépasse son solde restant.',
                                $facture->getNumero() ?: $facture->getId()
                            )
                        );
                    }

                    $totalAffecteCentimes += $montantCentimes;

                    $affectations[] = [
                        'facture' => $facture,
                        'montant' => number_format(
                            $montantCentimes / 100,
                            2,
                            '.',
                            ''
                        ),
                    ];
                }

                $montantReglementCentimes = (int) round(
                    (float) $montant * 100
                );

                if ($totalAffecteCentimes > $montantReglementCentimes) {
                    throw new \LogicException(
                        'Le total affecté dépasse le montant du règlement.'
                    );
                }

                // Règlement et affectations enregistrés ensemble
                $reglementService->enregistrerAvecAffectations(
                    fournisseur: $fournisseur,
                    exercice: $exercice,
                    dateReglement: $dateReglement,
                    montant: $montant,
                    reference: $reference !== '' ? $reference : null,
                    affectations: $affectations,
                );

                $this->addFlash(
                    'success',
                    'Le règlement fournisseur et ses affectations ont été enregistrés.'
                );

                return $this->redirectToRoute(
                    'app_reglement_fournisseur_new',
                    ['fournisseur' => $fournisseur->getId()]
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
                'factures' => $factures,
                'soldeFournisseur' => $soldeFournisseur,
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

    #[Route(
        '/{id}/affectations/{affectationId}/supprimer',
        name: 'app_reglement_fournisseur_affectation_delete',
        requirements: [
            'id' => '\d+',
            'affectationId' => '\d+',
        ],
        methods: ['POST']
    )]
    public function supprimerAffectation(
        ReglementFournisseur $reglement,
        int $affectationId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        if ($reglement->getExercice()->isCloture()) {
            $this->addFlash(
                'danger',
                'Impossible de modifier les affectations d’un exercice clôturé.'
            );

            return $this->redirectToRoute(
                'app_reglement_fournisseur_show',
                ['id' => $reglement->getId()]
            );
        }

        if (!$this->isCsrfTokenValid(
            'delete-affectation-' . $affectationId,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $affectation = $entityManager->find(
            AffectationReglementFournisseur::class,
            $affectationId
        );

        if (
            $affectation === null
            || $affectation->getReglement()?->getId() !== $reglement->getId()
        ) {
            throw $this->createNotFoundException(
                'Affectation introuvable pour ce règlement.'
            );
        }

        // Suppression de l'affectation uniquement.
        $entityManager->remove($affectation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’affectation a été supprimée.'
        );

        return $this->redirectToRoute(
            'app_reglement_fournisseur_show',
            ['id' => $reglement->getId()]
        );
    }
}
