<?php

namespace App\Controller\Travaux;

use App\Entity\Travaux\Chantier;
use App\Entity\Travaux\VoteComplementaireChantier;
use App\Form\Travaux\VoteComplementaireChantierType;
use App\Form\Travaux\ChantierType;
use App\Repository\Travaux\ChantierRepository;
use App\Repository\FactureFournisseurRepository;
use App\Enum\FactureFournisseurType;
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

    #[Route(
        '/new',
        name: 'app_chantier_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ContexteExerciceService $contexteExerciceService,
        InitialisationComptesChantierService $initialisationComptesChantierService,
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

            $entityManager->persist($chantier);
            $entityManager->flush();

            $initialisationComptesChantierService->initialiser($chantier);

            $this->addFlash(
                'success',
                'Le chantier a été créé.'
            );

            return $this->redirectToRoute(
                'app_chantier_index'
            );
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

        $totalFacture = 0.0;
        $totalRealise = 0.0;

        $totalRealise = $coutReelChantierService->calculer($chantier);

        $montantTotalVote =
            $chantier->getMontantTotalVote();

        $totalAppels = round($totalAppels, 2);
        $totalFacture = round($totalFacture, 2);
        $totalRealise = round($totalRealise, 2);

        $resteAAppeler = round(
            $montantTotalVote - $totalAppels,
            2
        );

        $provisionDisponible = round(
            $totalAppels - $totalRealise,
            2
        );

        $simulation = $simulationClotureChantierService->simuler(
            $chantier,
            $totalRealise
        );

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
    ): Response {
        if (!$this->isCsrfTokenValid(
            'generer-appel-' . $chantier->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
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
}
