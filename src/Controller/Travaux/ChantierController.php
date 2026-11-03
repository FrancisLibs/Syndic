<?php

namespace App\Controller\Travaux;

use App\Entity\Travaux\Chantier;
use App\Entity\Travaux\VoteComplementaireChantier;
use App\Form\Travaux\VoteComplementaireChantierType;
use App\Form\Travaux\ChantierType;
use App\Repository\Travaux\ChantierRepository;
use App\Repository\FactureFournisseurRepository;
use App\Repository\ResolutionRepository;
use App\Service\Travaux\CreationResolutionChantierService;
use App\Enum\AssembleeGeneraleStatut;
use App\Service\ContexteExerciceService;
use App\Service\Travaux\GenerateurAppelChantierService;
use App\Service\Travaux\InitialisationComptesChantierService;
use App\Service\Travaux\SimulationClotureChantierService;
use App\Service\Travaux\CoutReelChantierService;
use App\Service\Travaux\ClotureChantierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chantier')]
final class ChantierController extends AbstractController
{
    #[Route(
        '/',
        name: 'app_chantier_index',
        methods: ['GET']
    )]
    public function index(
        ChantierRepository $chantierRepository,
        ContexteExerciceService $contexteExerciceService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice disponible.'
            );
        }

        $chantiers = $chantierRepository->findBy(
            ['copropriete' => $exercice->getCopropriete()],
            ['dateVote' => 'DESC']
        );

        return $this->render('chantier/index.html.twig', [
            'chantiers' => $chantiers,
            'exercice' => $exercice,
        ]);
    }

    #[Route('/new', name: 'app_chantier_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ContexteExerciceService $contexteExerciceService,
        InitialisationComptesChantierService $initialisationComptesChantierService,
        CreationResolutionChantierService $creationResolutionChantierService,
    ): Response {

        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice disponible.'
            );
        }

        $chantier = new Chantier();

        $chantier->setCopropriete(
            $exercice->getCopropriete()
        );

        $form = $this->createForm(
            ChantierType::class,
            $chantier
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $resolutionCreee = false;

            try {

                $entityManager->wrapInTransaction(
                    function () use (
                        $entityManager,
                        $chantier,
                        $initialisationComptesChantierService,
                        $creationResolutionChantierService,
                        &$resolutionCreee
                    ): void {

                        // Enregistrement du chantier
                        $entityManager->persist($chantier);
                        $entityManager->flush();

                        // Initialisation des comptes comptables
                        $initialisationComptesChantierService
                            ->initialiser($chantier);

                        // Création automatique de la résolution
                        $resolutionCreee = $creationResolutionChantierService
                            ->creerPourChantier($chantier);
                    }
                );

                if ($resolutionCreee) {

                    $this->addFlash(
                        'success',
                        'Le chantier a été créé et une résolution '
                            . 'a été ajoutée à l’assemblée générale en préparation.'
                    );
                } else {

                    $this->addFlash(
                        'warning',
                        'Le chantier a été créé, mais aucune résolution '
                            . 'n’a été ajoutée automatiquement. '
                            . 'Vérifiez qu’une seule AG est en préparation.'
                    );
                }

                return $this->redirectToRoute(
                    'app_chantier_index'
                );
            } catch (\Throwable $e) {

                $this->addFlash(
                    'danger',
                    'Impossible de créer le chantier : '
                        . $e->getMessage()
                );
            }
        }

        return $this->render(
            'chantier/new.html.twig',
            [
                'chantier' => $chantier,
                'form' => $form,
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_chantier_show',
        methods: ['GET']
    )]
    public function show(
        Chantier $chantier,
        FactureFournisseurRepository $factureFournisseurRepository,
        SimulationClotureChantierService $simulationClotureChantierService,
        CoutReelChantierService $coutReelChantierService,
        ClotureChantierService $clotureChantierService,
    ): Response {
        /*
     * Appels de fonds
     */
        $totalAppels = 0.0;

        foreach ($chantier->getAppels() as $appel) {
            $totalAppels += (float) $appel->getMontantTotal();
        }

        /*
     * Factures du chantier
     */
        $factures =
            $factureFournisseurRepository
            ->findPourChantier($chantier);

        $totalRealise = $coutReelChantierService->calculer($chantier);

        // Le coût réel correspond aux factures comptabilisées,
        // déduction faite des avoirs.
        $totalFacture = $totalRealise;

        $montantTotalVote =
            $chantier->getMontantTotalVote();

        $totalAppels = round($totalAppels, 2);
        $totalFacture = round($totalFacture, 2);
        $totalRealise = round($totalRealise, 2);

        $resteAAppeler = null;
        $provisionDisponible = null;

        if ($chantier->estFinanceParAppels()) {

            $resteAAppeler = round(
                $montantTotalVote - $totalAppels,
                2
            );

            $provisionDisponible = round(
                $totalAppels - $totalRealise,
                2
            );
        }

        $simulation = [
            'lignes' => [],
            'totalAppels' => 0.0,
            'coutReel' => $totalRealise,
            'regularisationTotale' => 0.0,
        ];

        if (
            $chantier->estFinanceParAppels()
            && !$chantier->getAppels()->isEmpty()
        ) {
            $simulation = $simulationClotureChantierService->simuler(
                $chantier,
                $totalRealise
            );
        }

        $controleCloture = [
            'valide' => false,
            'message' => '',
        ];

        try {
            $clotureChantierService->verifier($chantier);

            $controleCloture = [
                'valide' => true,
                'message' => 'Les contrôles préalables à la clôture sont satisfaits.',
            ];
        } catch (\LogicException $e) {
            $controleCloture['message'] = $e->getMessage();
        }

        return $this->render(
            'chantier/show.html.twig',
            [
                'chantier' => $chantier,
                'factures' => $factures,
                'totalAppels' => $totalAppels,
                'resteAAppeler' => $resteAAppeler,
                'totalFacture' => $totalFacture,
                'totalRealise' => $totalRealise,
                'provisionDisponible' => $provisionDisponible,
                'simulation' => $simulation,
                'controleCloture' => $controleCloture,
            ]
        );
    }

    #[Route(
        '/{id}/generer-appel',
        name: 'app_chantier_generer_appel',
        methods: ['POST']
    )]
    public function genererAppel(
        Chantier $chantier,
        Request $request,
        GenerateurAppelChantierService $generateurAppelChantierService,
        ResolutionRepository $resolutionRepository,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'generer-appel-' . $chantier->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        // Un chantier financé par charges courantes
        // ne doit pas générer d'appel de fonds spécifique.
        if (!$chantier->estFinanceParAppels()) {

            $this->addFlash(
                'danger',
                'Ce chantier est financé par les charges courantes. '
                    . 'Aucun appel de fonds spécifique ne peut être généré.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        // Aucun nouvel appel sur un chantier clôturé.
        if ($chantier->isCloture()) {

            $this->addFlash(
                'danger',
                'Ce chantier est clôturé. '
                    . 'Il n’est plus possible de générer un appel de fonds.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $resolutions = $resolutionRepository->findBy([
            'chantier' => $chantier,
            'adoptee' => true,
        ]);

        $chantierApprouve = false;

        foreach ($resolutions as $resolution) {
            $assembleeGenerale = $resolution->getAssembleeGenerale();

            if (
                $assembleeGenerale !== null
                && $assembleeGenerale->getStatut()
                === AssembleeGeneraleStatut::CLOTUREE
            ) {
                $chantierApprouve = true;
                break;
            }
        }

        if (!$chantierApprouve) {
            $this->addFlash(
                'danger',
                'L’appel de fonds ne peut être généré que si les travaux ont été approuvés par une Assemblée générale clôturée.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $dateAppel = $request->request->get('date_appel');

        if (!$dateAppel) {
            $this->addFlash(
                'danger',
                'La date de l\'appel est obligatoire.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        try {
            $dateAppel = new \DateTimeImmutable($dateAppel);

            $generateurAppelChantierService->generer(
                $chantier,
                $dateAppel
            );

            $this->addFlash(
                'success',
                'L\'appel de fonds a été généré.'
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_chantier_show',
            ['id' => $chantier->getId()]
        );
    }

    #[Route(
        '/{id}/vote-complementaire',
        name: 'app_chantier_vote_complementaire',
        methods: ['GET', 'POST']
    )]
    public function voteComplementaire(
        Chantier $chantier,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($chantier->isCloture()) {
            $this->addFlash(
                'danger',
                'Impossible d’ajouter un vote complémentaire à un chantier clôturé.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $vote = new VoteComplementaireChantier();
        $vote->setChantier($chantier);

        $form = $this->createForm(
            VoteComplementaireChantierType::class,
            $vote
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vote);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le vote complémentaire a été enregistré.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        return $this->render(
            'chantier/vote_complementaire.html.twig',
            [
                'chantier' => $chantier,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/vote-complementaire/{id}/generer-appel',
        name: 'app_chantier_vote_complementaire_generer_appel',
        methods: ['POST']
    )]
    public function genererAppelComplementaire(
        VoteComplementaireChantier $vote,
        Request $request,
        GenerateurAppelChantierService $generateurAppelChantierService,
        ResolutionRepository $resolutionRepository,
    ): Response {
        $chantier = $vote->getChantier();

        if (!$chantier) {
            throw $this->createNotFoundException(
                'Le chantier associé au vote complémentaire est introuvable.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'generer-appel-complementaire-' . $vote->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        // Interdire les appels complémentaires
        // pour les chantiers financés par charges courantes.
        if (!$chantier->estFinanceParAppels()) {

            $this->addFlash(
                'danger',
                'Ce chantier est financé par les charges courantes. '
                    . 'Aucun appel complémentaire spécifique ne peut être généré.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        // Interdire les appels sur un chantier clôturé.
        if ($chantier->isCloture()) {

            $this->addFlash(
                'danger',
                'Ce chantier est clôturé. '
                    . 'Il n’est plus possible de générer un appel complémentaire.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $resolutions = $resolutionRepository->findBy([
            'voteComplementaireChantier' => $vote,
            'adoptee' => true,
        ]);

        $voteApprouve = false;

        foreach ($resolutions as $resolution) {
            $assembleeGenerale = $resolution->getAssembleeGenerale();

            if (
                $assembleeGenerale !== null
                && $assembleeGenerale->getStatut()
                === AssembleeGeneraleStatut::CLOTUREE
            ) {
                $voteApprouve = true;
                break;
            }
        }

        if (!$voteApprouve) {
            $this->addFlash(
                'danger',
                'L’appel complémentaire ne peut être généré que si son financement a été approuvé par une Assemblée générale clôturée.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $dateAppel = $request->request->get('date_appel');

        if (!$dateAppel) {
            $this->addFlash(
                'danger',
                'La date de l\'appel est obligatoire.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        try {
            $dateAppel = new \DateTimeImmutable($dateAppel);

            $appelFond =
                $generateurAppelChantierService
                ->genererComplementaire(
                    $vote,
                    $dateAppel
                );

            $this->addFlash(
                'success',
                sprintf(
                    'L\'appel complémentaire %s de %.2f € a été généré.',
                    $appelFond->getNumero(),
                    (float) $appelFond->getMontantTotal()
                )
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_chantier_show',
            ['id' => $chantier->getId()]
        );
    }

    #[Route(
        '/{id}/cloturer',
        name: 'app_chantier_cloturer',
        methods: ['POST']
    )]
    public function cloturer(
        Chantier $chantier,
        Request $request,
        ClotureChantierService $clotureChantierService,
    ): Response {

        if (!$this->isCsrfTokenValid(
            'cloturer-chantier-' . $chantier->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $dateSaisie = $request->request->get('date_regularisation');

        if (!is_string($dateSaisie) || $dateSaisie === '') {
            $this->addFlash(
                'danger',
                'La date de régularisation est obligatoire.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        $dateRegularisation = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dateSaisie
        );

        if (
            $dateRegularisation === false
            || $dateRegularisation->format('Y-m-d') !== $dateSaisie
        ) {
            $this->addFlash(
                'danger',
                'La date de régularisation est invalide.'
            );

            return $this->redirectToRoute(
                'app_chantier_show',
                ['id' => $chantier->getId()]
            );
        }

        try {
            $clotureChantierService->cloturer(
                $chantier,
                $dateRegularisation
            );

            if ($chantier->estFinanceParChargesCourantes()) {
                $message = 'Le chantier a été clôturé. '
                    . 'Les dépenses restent intégrées aux charges courantes de l’exercice.';
            } else {
                $message = 'Le chantier a été clôturé. '
                    . 'Les éventuelles régularisations comptables ont été effectuées.';
            }

            $this->addFlash('success', $message);

        } catch (\LogicException $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_chantier_show',
            ['id' => $chantier->getId()]
        );
    }
}
