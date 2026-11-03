<?php

namespace App\Controller;

use App\Entity\ApprobationComptes;
use App\Entity\AssembleeGenerale;
use App\Entity\Resolution;
use App\Entity\Coproprietaire;
use App\Entity\PresenceAssembleeGenerale;
use App\Form\AssembleeGeneraleType;
use App\Form\ResolutionType;
use App\Form\AssembleeGeneraleBureauType;
use App\Form\FeuillePresenceAssembleeGeneraleType;
use App\Form\SecondVoteResolutionType;
use App\Form\VotesCorrespondanceType;
use App\Form\AssembleeGenerale\DecisionResolutionType;
use App\Form\ClotureAssembleeGeneraleType;
use App\Repository\AssembleeGeneraleRepository;
use App\Repository\BudgetRepository;
use App\Repository\ExerciceRepository;
use App\Repository\EcritureRepository;
use App\Repository\ApprobationComptesRepository;
use App\Repository\MandatCoproprieteRepository;
use App\Service\Cloture\SimulationRegularisationService;
use App\Service\PdfService;
use App\Service\EtatCompteCoproprietaireService;
use App\Service\AssembleeGenerale\VoteResolutionService;
use App\Service\AssembleeGenerale\ApprobationComptesService;
use App\Service\AssembleeGenerale\PresenceAssembleeGeneraleService;
use App\Service\EtatRepartitionChargesService;
use App\Service\Eau\EauService;
use App\Enum\PresenceStatut;
use App\Enum\AssembleeGeneraleStatut;
use App\Enum\MajoriteResolution;
use App\Enum\VoteChoix;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/assemblee/generale')]
final class AssembleeGeneraleController extends AbstractController
{
    #[Route(name: 'app_assemblee_generale_index', methods: ['GET'])]
    public function index(
        AssembleeGeneraleRepository $assembleeGeneraleRepository
    ): Response {
        return $this->render('assemblee_generale/index.html.twig', [
            'assemblee_generales' => $assembleeGeneraleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_assemblee_generale_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ExerciceRepository $exerciceRepository,
    ): Response {
        $assembleeGenerale = new AssembleeGenerale();

        $exerciceId = $request->query->getInt('exercice');

        if ($exerciceId > 0) {
            $exercice = $exerciceRepository->find($exerciceId);

            if ($exercice !== null) {
                $assembleeGenerale->setExercice($exercice);
            }
        }

        $form = $this->createForm(AssembleeGeneraleType::class, $assembleeGenerale);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($assembleeGenerale);
            $entityManager->flush();

            return $this->redirectToRoute('app_assemblee_generale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('assemblee_generale/new.html.twig', [
            'assemblee_generale' => $assembleeGenerale,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_assemblee_generale_show', methods: ['GET'])]
    public function show(
        AssembleeGenerale $assembleeGenerale,
        ApprobationComptesRepository $approbationComptesRepository,
        ExerciceRepository $exerciceRepository,
        BudgetRepository $budgetRepository,
    ): Response {

        $approbationComptes =
            $approbationComptesRepository->findOneBy([
                'exercice' => $assembleeGenerale->getExercice(),
            ]);

        $exerciceSuivant = $exerciceRepository->findSuivant(
            $assembleeGenerale->getExercice()
        );

        $budgetProjet = $exerciceSuivant
            ? $budgetRepository->findPourExercice($exerciceSuivant)
            : null;

        $nombreVotesCorrespondance = 0;

        foreach ($assembleeGenerale->getPresences() as $presence) {
            if ($presence->getStatut() === PresenceStatut::VOTE_CORRESPONDANCE) {
                $nombreVotesCorrespondance++;
            }
        }

        $nombreVotesCorrespondanceSaisis = 0;

        foreach ($assembleeGenerale->getPresences() as $presence) {

            if ($presence->getStatut() !== PresenceStatut::VOTE_CORRESPONDANCE) {
                continue;
            }

            $formulaireComplet = true;

            foreach ($assembleeGenerale->getResolutions() as $resolution) {

                if ($resolution->getMajorite() === null) {
                    continue;
                }

                $voteTrouve = false;

                foreach ($resolution->getVotes() as $vote) {
                    if (
                        $vote->getTour() === 1
                        && $vote->getCoproprietaire()
                        === $presence->getCoproprietaire()
                        && $vote->getChoix() !== null
                    ) {
                        $voteTrouve = true;
                        break;
                    }
                }

                if (!$voteTrouve) {
                    $formulaireComplet = false;
                    break;
                }
            }

            if ($formulaireComplet) {
                $nombreVotesCorrespondanceSaisis++;
            }
        }

        return $this->render('assemblee_generale/show.html.twig', [
            'assemblee_generale' => $assembleeGenerale,
            'approbation_comptes' => $approbationComptes,
            'budget_projet' => $budgetProjet,
            'nombre_votes_correspondance' => $nombreVotesCorrespondance,
            'nombre_votes_correspondance_saisis' => $nombreVotesCorrespondanceSaisis,
        ]);
    }

    #[Route(
        '/{id}/proces-verbal',
        name: 'app_assemblee_generale_proces_verbal',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function procesVerbal(
        AssembleeGenerale $assembleeGenerale
    ): Response {
        $participation = $this->calculerParticipation(
            $assembleeGenerale
        );

        $defaillantsAmendements = [];

        foreach ($assembleeGenerale->getResolutions() as $resolution) {

            if (!$resolution->isAmendeeEnSeance()) {
                continue;
            }

            foreach ($resolution->getVotes() as $vote) {

                if (
                    $vote->getTour() !== 1
                    || $vote->getChoix() !== VoteChoix::POUR
                ) {
                    continue;
                }

                foreach ($assembleeGenerale->getPresences() as $presence) {

                    if (
                        $presence->getCoproprietaire()
                        === $vote->getCoproprietaire()
                        && $presence->getStatut()
                        === PresenceStatut::VOTE_CORRESPONDANCE
                    ) {
                        $defaillantsAmendements[$resolution->getId()][] = [
                            'coproprietaire' => $vote->getCoproprietaire(),
                            'nombreVoix' => $vote->getNombreVoix(),
                        ];

                        break;
                    }
                }
            }
        }

        return $this->render(
            'assemblee_generale/proces_verbal.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'participation' => $participation,
                'defaillants_amendements' => $defaillantsAmendements,
            ]
        );
    }

    #[Route(
        '/{id}/proces-verbal/pdf',
        name: 'app_assemblee_generale_proces_verbal_pdf',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function procesVerbalPdf(
        AssembleeGenerale $assembleeGenerale,
        PdfService $pdfService,
    ): Response {
        $participation = $this->calculerParticipation(
            $assembleeGenerale
        );

        $defaillantsAmendements = [];

        foreach ($assembleeGenerale->getResolutions() as $resolution) {

            if (!$resolution->isAmendeeEnSeance()) {
                continue;
            }

            foreach ($resolution->getVotes() as $vote) {

                if (
                    $vote->getTour() !== 1
                    || $vote->getChoix() !== VoteChoix::POUR
                ) {
                    continue;
                }

                foreach ($assembleeGenerale->getPresences() as $presence) {

                    if (
                        $presence->getCoproprietaire()
                        === $vote->getCoproprietaire()
                        && $presence->getStatut()
                        === PresenceStatut::VOTE_CORRESPONDANCE
                    ) {
                        $defaillantsAmendements[$resolution->getId()][] = [
                            'coproprietaire' => $vote->getCoproprietaire(),
                            'nombreVoix' => $vote->getNombreVoix(),
                        ];

                        break;
                    }
                }
            }
        }

        $html = $this->renderView(
            'assemblee_generale/pdf/proces_verbal.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'participation' => $participation,
                'defaillants_amendements' => $defaillantsAmendements,
                'css' => $pdfService->chargerCss(),
            ]
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'inline; filename="proces-verbal-ag-%s.pdf"',
                    $assembleeGenerale->getExercice()->getNom()
                ),
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_assemblee_generale_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée et ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $form = $this->createForm(AssembleeGeneraleType::class, $assembleeGenerale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_assemblee_generale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('assemblee_generale/edit.html.twig', [
            'assemblee_generale' => $assembleeGenerale,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_assemblee_generale_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Une assemblée générale clôturée ne peut pas être supprimée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($this->isCsrfTokenValid('delete' . $assembleeGenerale->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($assembleeGenerale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_assemblee_generale_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/convocation',
        name: 'app_assemblee_generale_convocation',
        methods: ['GET']
    )]
    public function convocation(
        AssembleeGenerale $assembleeGenerale,
        SimulationRegularisationService $simulationRegularisationService
    ): Response {
        $exercice = $assembleeGenerale->getExercice();

        $simulation = $simulationRegularisationService
            ->simulerRegularisation($exercice);

        return $this->render(
            'assemblee_generale/convocation.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'simulation' => $simulation,
            ]
        );
    }

    #[Route(
        '/{id}/convocation/{coproprietaire}/pdf',
        name: 'app_assemblee_generale_convocation_pdf',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function convocationPdf(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        PdfService $pdfService
    ): Response {
        $exercice = $assembleeGenerale->getExercice();

        $css = $pdfService->chargerCss();

        $html = $this->renderView(
            'assemblee_generale/pdf/convocation.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $exercice,
                'coproprietaire' => $coproprietaire,
                'css' => $css,
            ]
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="convocation-ag-'
                    . $exercice->getNom()
                    . '-'
                    . $coproprietaire->getId()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/{id}/convocation/{coproprietaire}/dossier/pdf',
        name: 'app_assemblee_generale_dossier_convocation_pdf',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function dossierConvocationPdf(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        PdfService $pdfService,
        SimulationRegularisationService $simulationRegularisationService,
        EcritureRepository $ecritureRepository,
        ExerciceRepository $exerciceRepository,
        BudgetRepository $budgetRepository,
        EtatRepartitionChargesService $etatRepartitionChargesService,
        EauService $eauService,
        EtatCompteCoproprietaireService $etatCompteService,
        MandatCoproprieteRepository $mandatCoproprieteRepository
    ): Response {

        $exercice = $assembleeGenerale->getExercice();

        $etatCompte = $etatCompteService->construire(
            $coproprietaire,
            $exercice
        );

        $donneesBalance =
            $ecritureRepository->calculerBalance($exercice);

        $etatRepartitionCharges =
            $etatRepartitionChargesService->construire($exercice);

        $detailChargesCoproprietaire =
            $etatRepartitionCharges['groupes'][$coproprietaire->getId()]
            ?? null;

        if ($detailChargesCoproprietaire === null) {
            throw $this->createNotFoundException(
                'Aucune répartition des charges trouvée pour ce copropriétaire.'
            );
        }

        try {
            $calculEau = $eauService->calculer($exercice);
        } catch (\DomainException $exception) {
            throw $this->createNotFoundException(
                $exception->getMessage()
            );
        }

        $lotsEauCoproprietaire = [];

        foreach ($calculEau->getLots() as $lotEau) {
            if (
                $lotEau->getCoproprietaire()->getId()
                === $coproprietaire->getId()
            ) {
                $lotsEauCoproprietaire[] = $lotEau;
            }
        }

        $exerciceSuivant = $exerciceRepository->findSuivant($exercice);

        $budgetReference = $budgetRepository->findPourExercice($exercice);

        $budgetProjet = $exerciceSuivant
            ? $budgetRepository->findPourExercice($exerciceSuivant)
            : null;

        $comparatif = [];

        if ($budgetReference && $budgetProjet) {

            foreach ($budgetReference->getLignes() as $ligne) {

                $typeCharge = $ligne->getTypeCharge();

                $comparatif[$typeCharge->getId()] = [
                    'typeCharge' => $typeCharge,
                    'montantReference' => (float) $ligne->getMontant(),
                    'montantProjet' => 0.0,
                ];
            }

            foreach ($budgetProjet->getLignes() as $ligne) {

                $typeCharge = $ligne->getTypeCharge();
                $id = $typeCharge->getId();

                if (!isset($comparatif[$id])) {
                    $comparatif[$id] = [
                        'typeCharge' => $typeCharge,
                        'montantReference' => 0.0,
                        'montantProjet' => 0.0,
                    ];
                }

                $comparatif[$id]['montantProjet'] =
                    (float) $ligne->getMontant();
            }
        }

        $syndicMandat = $mandatCoproprieteRepository
            ->findSyndicAlaDate(
                $exercice->getCopropriete(),
                $assembleeGenerale->getDate()
            );

        $syndic = $syndicMandat?->getCoproprietaire();

        $dateLimiteVote = $assembleeGenerale
            ->getDate()
            ->modify('-4 days');

        // Données nécessaires à la répartition individuelle
        $data = $this->preparerRepartitionIndividuelle(
            $assembleeGenerale,
            $coproprietaire,
            $simulationRegularisationService,
            $ecritureRepository
        );

        // Données communes au dossier
        $data['exercice'] = $exercice;
        $data['coproprietaire'] = $coproprietaire;
        $data['css'] = $pdfService->chargerCss();
        $data['exercice_suivant'] = $exerciceSuivant;
        $data['budget_reference'] = $budgetReference;
        $data['budget_projet'] = $budgetProjet;
        $data['comparatif'] = $comparatif;
        $data['balance'] = $donneesBalance['balance'];
        $data['totalDebit'] = $donneesBalance['totalDebit'];
        $data['totalCredit'] = $donneesBalance['totalCredit'];
        $data['totalSoldeDebiteur'] = $donneesBalance['totalSoldeDebiteur'];
        $data['totalSoldeCrediteur'] = $donneesBalance['totalSoldeCrediteur'];
        $data['equilibree'] = $donneesBalance['equilibree'];
        $data['calcul'] = $calculEau;
        $data['syndic'] = $syndic;
        $data['date_limite_vote'] = $dateLimiteVote;
        $data['detail_charges'] = $detailChargesCoproprietaire;
        $data['lots_eau_coproprietaire'] = $lotsEauCoproprietaire;



        $data = array_merge(
            $data,
            $etatRepartitionCharges
        );

        if ($etatCompte !== null) {
            $data['ligneCompte'] = $etatCompte;
        }

        $html = $this->renderView(
            'assemblee_generale/pdf/dossier_convocation.html.twig',
            $data
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="dossier-convocation-ag-'
                    . $exercice->getNom()
                    . '-'
                    . $coproprietaire->getId()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/{id}/convocation/repartition/{coproprietaire}',
        name: 'app_assemblee_generale_repartition_individuelle',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function repartitionIndividuelle(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        SimulationRegularisationService $simulationRegularisationService,
        EcritureRepository $ecritureRepository
    ): Response {
        $donnees = $this->preparerRepartitionIndividuelle(
            $assembleeGenerale,
            $coproprietaire,
            $simulationRegularisationService,
            $ecritureRepository
        );

        return $this->render(
            'assemblee_generale/repartition_individuelle.html.twig',
            $donnees
        );
    }

    private function preparerRepartitionIndividuelle(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        SimulationRegularisationService $simulationRegularisationService,
        EcritureRepository $ecritureRepository
    ): array {
        $exercice = $assembleeGenerale->getExercice();

        $simulation = $simulationRegularisationService
            ->simulerRegularisation($exercice);

        $ligneCoproprietaire = null;

        foreach ($simulation->lignes as $ligne) {
            if (
                $ligne->coproprietaire->getId()
                === $coproprietaire->getId()
            ) {
                $ligneCoproprietaire = $ligne;
                break;
            }
        }

        if ($ligneCoproprietaire === null) {
            throw $this->createNotFoundException(
                'Aucune répartition trouvée pour ce copropriétaire.'
            );
        }

        $compte = $coproprietaire->getCompte();

        if ($compte === null) {
            throw new \LogicException(
                'Aucun compte comptable n’est associé à ce copropriétaire.'
            );
        }

        $dateSituation = $exercice->getDateFin();

        $soldeAvantRepartition = $ecritureRepository
            ->calculerSoldeCompteADate(
                $exercice,
                $compte,
                $dateSituation
            );

        $soldeApresRepartition = round(
            $soldeAvantRepartition
                - $ligneCoproprietaire->getRegularisation(),
            2
        );

        return [
            'assemblee_generale' => $assembleeGenerale,
            'ligne' => $ligneCoproprietaire,
            'solde_avant_repartition' => $soldeAvantRepartition,
            'solde_apres_repartition' => $soldeApresRepartition,
            'date_situation' => $dateSituation,
        ];
    }

    #[Route(
        '/{id}/resolution/new',
        name: 'app_assemblee_generale_resolution_new',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function nouvelleResolution(
        AssembleeGenerale $assembleeGenerale,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Il n’est plus possible d’ajouter une résolution.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $resolution = new Resolution();

        $resolution->setAssembleeGenerale($assembleeGenerale);

        $form = $this->createForm(
            ResolutionType::class,
            $resolution,
            [
                'assemblee_generale' => $assembleeGenerale,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($resolution);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La résolution a été ajoutée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $assembleeGenerale->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/resolution_new.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/resolution/{id}/edit',
        name: 'app_assemblee_generale_resolution_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function modifierResolution(
        Resolution $resolution,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Les résolutions ne peuvent plus être modifiées.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $form = $this->createForm(
            ResolutionType::class,
            $resolution,
            [
                'assemblee_generale' => $assembleeGenerale,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La résolution a été modifiée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $resolution
                        ->getAssembleeGenerale()
                        ->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/resolution_edit.html.twig',
            [
                'resolution' => $resolution,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/resolution/{id}/decision',
        name: 'app_assemblee_generale_resolution_decision',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function decisionResolution(
        Resolution $resolution,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw $this->createNotFoundException(
                'Assemblée générale introuvable.'
            );
        }

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Les décisions ne peuvent plus être modifiées.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if (
            $resolution->getTantiemesPour() !== null
            || $resolution->getTantiemesContre() !== null
            || $resolution->getTantiemesAbstention() !== null
        ) {
            $this->addFlash(
                'warning',
                'La décision ne peut plus être modifiée car la résolution a déjà été votée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $form = $this->createForm(
            DecisionResolutionType::class,
            $resolution
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La décision de l’Assemblée Générale a été enregistrée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        return $this->render(
            'assemblee_generale/resolution_decision.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'resolution' => $resolution,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/initialiser-ordre-du-jour',
        name: 'app_assemblee_generale_initialiser_ordre_du_jour',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function initialiserOrdreDuJour(
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. L’ordre du jour ne peut plus être modifié.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if (!$assembleeGenerale->getResolutions()->isEmpty()) {
            $this->addFlash(
                'warning',
                'L’ordre du jour contient déjà des résolutions.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $exercice = $assembleeGenerale->getExercice();

        $resolutions = [
            [
                'numero' => 1,
                'titre' => 'Présents / Représentés',
                'texte' => 'Établissement de la feuille de présence et constatation des copropriétaires présents et représentés.',
                'majorite' => null,
            ],
            [
                'numero' => 2,
                'titre' => 'Désignation du Président de séance et constitution du bureau',
                'texte' => 'L’Assemblée Générale procède à la désignation du Président de séance et à la constitution du bureau.',
                'majorite' => MajoriteResolution::ARTICLE_24,
            ],
            [
                'numero' => 3,
                'titre' => 'Approbation des comptes',
                'texte' => sprintf(
                    'L’Assemblée Générale, après avoir pris connaissance des documents comptables, approuve les comptes de %s.',
                    $exercice->getNom()
                ),
                'majorite' => MajoriteResolution::ARTICLE_24,
            ],
            [
                'numero' => 4,
                'titre' => 'Quitus au syndic',
                'texte' => sprintf(
                    'L’Assemblée Générale donne quitus au syndic pour sa gestion au titre de %s.',
                    $exercice->getNom()
                ),
                'majorite' => MajoriteResolution::ARTICLE_24,
            ],
            [
                'numero' => 5,
                'titre' => 'Désignation du syndic bénévole',
                'texte' => 'L\'Assemblée Générale désigne Francis Libs en qualité de syndic bénévole
                de la copropriété.',
                'majorite' => MajoriteResolution::ARTICLE_25,
            ],
            [
                'numero' => 6,
                'titre' => 'Approbation du budget prévisionnel',
                'texte' => 'L\'Assemblée Générale approuve le budget prévisionnel présenté avec la convocation.',
                'majorite' => MajoriteResolution::ARTICLE_24,
            ],
        ];

        foreach ($resolutions as $donnees) {
            $resolution = new Resolution();

            $resolution
                ->setAssembleeGenerale($assembleeGenerale)
                ->setNumero($donnees['numero'])
                ->setTitre($donnees['titre'])
                ->setTexte($donnees['texte'])
                ->setMajorite($donnees['majorite']);

            $entityManager->persist($resolution);
        }

        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’ordre du jour a été initialisé.'
        );

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    #[Route(
        '/{id}/reinitialiser',
        name: 'app_assemblee_generale_reinitialiser',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function reinitialiser(
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager
    ): Response {

        /*
     * Suppression des votes puis des résolutions.
     */
        foreach ($assembleeGenerale->getResolutions() as $resolution) {

            foreach ($resolution->getVotes() as $vote) {
                $entityManager->remove($vote);
            }

            $entityManager->remove($resolution);
        }

        /*
     * Suppression des présences.
     */
        foreach ($assembleeGenerale->getPresences() as $presence) {
            $entityManager->remove($presence);
        }

        /*
     * Remise de l'AG en préparation.
     */
        $assembleeGenerale
            ->setStatut(AssembleeGeneraleStatut::PREPARATION)
            ->setPresidentSeance(null)
            ->setSecretaireSeance(null)
            ->setScrutateur(null)
            ->setHeureCloture(null)
            ->setTantiemesPresentsRepresentes(null)
            ->setNombrePresents(null)
            ->setNombreRepresentes(null)
            ->setNombrePouvoirs(null);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’Assemblée Générale a été réinitialisée.'
        );

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    #[Route(
        '/{id}/documents-comptables',
        name: 'app_assemblee_generale_documents_comptables',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function documentsComptables(
        AssembleeGenerale $assembleeGenerale
    ): Response {
        return $this->render(
            'assemblee_generale/documents_comptables.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $assembleeGenerale->getExercice(),
            ]
        );
    }

    #[Route(
        '/{id}/budget-previsionnel',
        name: 'app_assemblee_generale_budget_previsionnel',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function budgetPrevisionnel(
        AssembleeGenerale $assembleeGenerale,
        ExerciceRepository $exerciceRepository,
        BudgetRepository $budgetRepository,
        EcritureRepository $ecritureRepository,
    ): Response {

        $exercice = $assembleeGenerale->getExercice();

        $exerciceSuivant =
            $exerciceRepository->findSuivant($exercice);

        $budgetReference =
            $budgetRepository->findPourExercice($exercice);

        $budgetProjet = $exerciceSuivant
            ? $budgetRepository->findPourExercice($exerciceSuivant)
            : null;

        $comparatif = [];

        if ($budgetReference) {
            foreach ($budgetReference->getLignes() as $ligne) {
                $typeCharge = $ligne->getTypeCharge();

                $comparatif[$typeCharge->getId()] = [
                    'typeCharge' => $typeCharge,
                    'montantReference' => (float) $ligne->getMontant(),
                    'montantProjet' => 0.0,
                ];
            }
        }

        if ($budgetProjet) {
            foreach ($budgetProjet->getLignes() as $ligne) {
                $typeCharge = $ligne->getTypeCharge();
                $id = $typeCharge->getId();

                if (!isset($comparatif[$id])) {
                    $comparatif[$id] = [
                        'typeCharge' => $typeCharge,
                        'montantReference' => 0.0,
                        'montantProjet' => 0.0,
                    ];
                }

                $comparatif[$id]['montantProjet'] =
                    (float) $ligne->getMontant();
            }
        }

        $realise =
            $ecritureRepository
            ->calculerRealiseParTypeCharge($exercice);

        $budgetRealise = [];

        // Lignes du budget voté
        if ($budgetReference) {
            foreach ($budgetReference->getLignes() as $ligne) {
                $typeCharge = $ligne->getTypeCharge();

                $budgetRealise[$typeCharge->getId()] = [
                    'typeCharge' => $typeCharge,
                    'budget' => (float) $ligne->getMontant(),
                    'realise' => 0.0,
                ];
            }
        }

        // Charges réellement comptabilisées
        foreach ($realise as $ligne) {
            $typeChargeId = (int) $ligne['typeChargeId'];

            if (isset($budgetRealise[$typeChargeId])) {
                $budgetRealise[$typeChargeId]['realise'] =
                    (float) $ligne['montant'];
            }
        }

        return $this->render(
            'assemblee_generale/budget_previsionnel.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $exercice,
                'exercice_suivant' => $exerciceSuivant,
                'budget_reference' => $budgetReference,
                'budget_projet' => $budgetProjet,
                'comparatif' => $comparatif,
                'budget_realise' => $budgetRealise,
                'total_realise' => array_sum(
                    array_column($realise, 'montant')
                ),
            ]
        );
    }

    #[Route(
        '/{id}/budget-previsionnel/pdf',
        name: 'app_assemblee_generale_budget_previsionnel_pdf',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function budgetPrevisionnelPdf(
        AssembleeGenerale $assembleeGenerale,
        ExerciceRepository $exerciceRepository,
        BudgetRepository $budgetRepository,
        PdfService $pdfService
    ): Response {
        $exercice = $assembleeGenerale->getExercice();

        $exerciceSuivant =
            $exerciceRepository->findSuivant($exercice);

        $budgetReference =
            $budgetRepository->findPourExercice($exercice);

        $budgetProjet = $exerciceSuivant
            ? $budgetRepository->findPourExercice($exerciceSuivant)
            : null;

        if (!$exerciceSuivant || !$budgetReference || !$budgetProjet) {
            $this->addFlash(
                'danger',
                'Impossible de générer le PDF du budget prévisionnel.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_budget_previsionnel',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $comparatif = [];

        foreach ($budgetReference->getLignes() as $ligne) {
            $typeCharge = $ligne->getTypeCharge();

            $comparatif[$typeCharge->getId()] = [
                'typeCharge' => $typeCharge,
                'montantReference' => (float) $ligne->getMontant(),
                'montantProjet' => 0.0,
            ];
        }

        foreach ($budgetProjet->getLignes() as $ligne) {
            $typeCharge = $ligne->getTypeCharge();
            $id = $typeCharge->getId();

            if (!isset($comparatif[$id])) {
                $comparatif[$id] = [
                    'typeCharge' => $typeCharge,
                    'montantReference' => 0.0,
                    'montantProjet' => 0.0,
                ];
            }

            $comparatif[$id]['montantProjet'] =
                (float) $ligne->getMontant();
        }

        $css = $pdfService->chargerCss();

        $html = $this->renderView(
            'assemblee_generale/pdf/budget_previsionnel.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $exercice,
                'exercice_suivant' => $exerciceSuivant,
                'budget_reference' => $budgetReference,
                'budget_projet' => $budgetProjet,
                'comparatif' => $comparatif,
                'css' => $css,
            ]
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="budget-previsionnel-' .
                    $exerciceSuivant->getNom() .
                    '.pdf"',
            ]
        );
    }

    #[Route(
        '/{id}/presences',
        name: 'app_assemblee_generale_presences',
        methods: ['GET', 'POST']
    )]
    public function presences(
        Request $request,
        AssembleeGenerale $assembleeGenerale,
        PresenceAssembleeGeneraleService $presenceService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée et ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        try {
            $presenceService->initialiser($assembleeGenerale);
        } catch (\LogicException $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $assembleeGenerale->getId(),
                ]
            );
        }

        $form = $this->createForm(
            FeuillePresenceAssembleeGeneraleType::class,
            $assembleeGenerale
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $presenceService->recalculerTotaux($assembleeGenerale);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'La feuille de présence a été enregistrée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $assembleeGenerale->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/presences.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'presences' => $assembleeGenerale->getPresences(),
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/bureau',
        name: 'app_assemblee_generale_bureau',
        methods: ['GET', 'POST']
    )]
    public function bureau(
        Request $request,
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée et ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $form = $this->createForm(
            AssembleeGeneraleBureauType::class,
            $assembleeGenerale
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La constitution du bureau a été enregistrée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $assembleeGenerale->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/bureau.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/resolution/{id}/vote',
        name: 'app_assemblee_generale_resolution_vote',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function voterResolution(
        Resolution $resolution,
        Request $request,
        VoteResolutionService $voteService,
        EntityManagerInterface $entityManager
    ): Response {

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Les votes ne peuvent plus être modifiés.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($resolution->getMajorite() === null) {
            $this->addFlash(
                'warning',
                'Ce point de l’ordre du jour ne fait pas l’objet d’un vote.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($resolution->hasSecondVoteEnregistre()) {

            $this->addFlash(
                'warning',
                'Le premier vote ne peut plus être modifié car un second vote a déjà été enregistré.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $resolution
                        ->getAssembleeGenerale()
                        ->getId(),
                ]
            );
        }

        try {
            $voteService->initialiser($resolution);
        } catch (\LogicException $e) {
            $this->addFlash(
                'danger',
                $e->getMessage()
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                [
                    'id' => $resolution
                        ->getAssembleeGenerale()
                        ->getId(),
                ]
            );
        }

        $votesPremierTour = [];
        $votesCorrespondance = [];

        foreach ($resolution->getVotes() as $vote) {

            if ($vote->getTour() !== 1) {
                continue;
            }

            $presenceCorrespondante = null;

            foreach ($assembleeGenerale->getPresences() as $presence) {
                if (
                    $presence->getCoproprietaire()
                    === $vote->getCoproprietaire()
                ) {
                    $presenceCorrespondante = $presence;
                    break;
                }
            }

            if ($presenceCorrespondante === null) {
                continue;
            }

            if (
                $presenceCorrespondante->getStatut()
                === PresenceStatut::VOTE_CORRESPONDANCE
            ) {
                $votesCorrespondance[] = $vote;
                continue;
            }

            if (
                $presenceCorrespondante->getStatut() === PresenceStatut::PRESENT
                || $presenceCorrespondante->getStatut() === PresenceStatut::REPRESENTE
            ) {
                $votesPremierTour[] = $vote;
            }
        }

        $data = [
            'votes' => $votesPremierTour,
        ];

        $form = $this->createForm(
            SecondVoteResolutionType::class,
            $data
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $voteManquant = false;

            foreach ($votesPremierTour as $vote) {
                if ($vote->getChoix() === null) {
                    $voteManquant = true;
                    break;
                }
            }

            if ($voteManquant) {

                $this->addFlash(
                    'danger',
                    'Un vote doit être renseigné pour chaque copropriétaire présent ou représenté.'
                );
            } else {

                $voteService->recalculerTotaux(
                    $resolution,
                    1
                );

                $voteService->calculerResultat(
                    $resolution
                );

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Le vote de la résolution a été enregistré.'
                );

                return $this->redirectToRoute(
                    'app_assemblee_generale_show',
                    [
                        'id' => $resolution
                            ->getAssembleeGenerale()
                            ->getId(),
                    ]
                );
            }
        }

        return $this->render(
            'assemblee_generale/resolution_vote.html.twig',
            [
                'resolution' => $resolution,
                'assemblee_generale' =>
                $resolution->getAssembleeGenerale(),
                'presences' =>
                $resolution
                    ->getAssembleeGenerale()
                    ->getPresences(),
                'votes_correspondance' => $votesCorrespondance,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/resolution/{id}/reinitialiser-vote',
        name: 'app_assemblee_generale_resolution_reinitialiser_vote',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function reinitialiserVoteResolution(
        Resolution $resolution,
        EntityManagerInterface $entityManager
    ): Response {

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if (
            $assembleeGenerale->getStatut() !== AssembleeGeneraleStatut::EN_COURS
        ) {
            $this->addFlash(
                'warning',
                'Les votes en séance ne peuvent être enregistrés que lorsque l’Assemblée Générale est en cours.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($assembleeGenerale === null) {
            throw $this->createNotFoundException(
                'Assemblée générale introuvable.'
            );
        }

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Le vote ne peut plus être réinitialisé.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        foreach ($resolution->getVotes() as $vote) {
            $entityManager->remove($vote);
        }

        $resolution
            ->setTantiemesPour(null)
            ->setTantiemesContre(null)
            ->setTantiemesAbstention(null)
            ->setAdoptee(null)
            ->setSecondVoteNecessaire(false)
            ->setTantiemesPourSecondTour(null)
            ->setTantiemesContreSecondTour(null)
            ->setTantiemesAbstentionSecondTour(null);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le vote de la résolution a été réinitialisé.'
        );

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    #[Route(
        '/resolution/{id}/second-vote',
        name: 'app_assemblee_generale_resolution_second_vote',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function secondVoteResolution(
        Resolution $resolution,
        Request $request,
        VoteResolutionService $voteService,
        EntityManagerInterface $entityManager
    ): Response {

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw $this->createNotFoundException(
                'Assemblée générale introuvable.'
            );
        }

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Les votes ne peuvent plus être modifiés.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($assembleeGenerale === null) {
            throw $this->createNotFoundException(
                'Assemblée générale introuvable.'
            );
        }

        try {
            $voteService->initialiserSecondTour($resolution);
        } catch (\LogicException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $votesSecondTour = [];
        $votesCorrespondance = [];

        foreach ($resolution->getVotes() as $vote) {

            if ($vote->getTour() !== 2) {
                continue;
            }

            $presenceCorrespondante = null;

            foreach ($assembleeGenerale->getPresences() as $presence) {

                if (
                    $presence->getCoproprietaire()
                    === $vote->getCoproprietaire()
                ) {
                    $presenceCorrespondante = $presence;
                    break;
                }
            }

            if ($presenceCorrespondante === null) {
                continue;
            }

            if (
                $presenceCorrespondante->getStatut()
                === PresenceStatut::VOTE_CORRESPONDANCE
            ) {
                $votesCorrespondance[] = $vote;
                continue;
            }

            if (
                $presenceCorrespondante->getStatut()
                === PresenceStatut::PRESENT
                || $presenceCorrespondante->getStatut()
                === PresenceStatut::REPRESENTE
            ) {
                $votesSecondTour[] = $vote;
            }
        }

        $data = [
            'votes' => $votesSecondTour,
        ];

        $form = $this->createForm(
            SecondVoteResolutionType::class,
            $data
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $voteManquant = false;

            foreach ($votesSecondTour as $vote) {
                if ($vote->getChoix() === null) {
                    $voteManquant = true;
                    break;
                }
            }

            if ($voteManquant) {

                $this->addFlash(
                    'danger',
                    'Un vote doit être renseigné pour chaque copropriétaire présent ou représenté.'
                );
            } else {

                $voteService->calculerResultatSecondTour(
                    $resolution
                );

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Le second vote a été enregistré.'
                );

                return $this->redirectToRoute(
                    'app_assemblee_generale_show',
                    [
                        'id' => $assembleeGenerale->getId(),
                    ]
                );
            }
        }

        return $this->render(
            'assemblee_generale/resolution_second_vote.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'resolution' => $resolution,
                'presences' => $assembleeGenerale->getPresences(),
                'votes_correspondance' => $votesCorrespondance,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/resolution/{id}/comptabiliser-approbation',
        name: 'app_assemblee_generale_comptabiliser_approbation',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function comptabiliserApprobation(
        Resolution $resolution,
        ApprobationComptesRepository $approbationRepository,
        ExerciceRepository $exerciceRepository,
        ApprobationComptesService $approbationService,
        EntityManagerInterface $entityManager
    ): Response {
        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw $this->createNotFoundException(
                'Assemblée générale introuvable.'
            );
        }

        $exercice = $assembleeGenerale->getExercice();

        try {
            if ($resolution->getTitre() !== 'Approbation des comptes') {
                throw new \LogicException(
                    'Cette résolution ne concerne pas l’approbation des comptes.'
                );
            }

            if ($resolution->isAdoptee() !== true) {
                throw new \LogicException(
                    'La résolution d’approbation des comptes n’a pas été adoptée.'
                );
            }

            $dateAssembleeGenerale = $assembleeGenerale->getDate();

            if ($dateAssembleeGenerale === null) {
                throw new \LogicException(
                    'La date de l’Assemblée Générale doit être renseignée.'
                );
            }

            $exerciceComptabilisation =
                $exerciceRepository->findPourDate(
                    $dateAssembleeGenerale
                );

            if ($exerciceComptabilisation === null) {
                throw new \LogicException(
                    'Aucun exercice comptable ne correspond à la date de l’Assemblée Générale.'
                );
            }

            $approbation =
                $approbationRepository->findOneBy([
                    'exercice' => $exercice,
                ]);

            if ($approbation === null) {
                $approbation = new ApprobationComptes();

                $approbation->setExercice($exercice);

                $entityManager->persist($approbation);
            }

            if ($approbation->getOperation() !== null) {
                throw new \LogicException(
                    'L’approbation des comptes a déjà été comptabilisée.'
                );
            }

            $approbation
                ->setDateAssembleeGenerale(
                    \DateTimeImmutable::createFromInterface(
                        $dateAssembleeGenerale
                    )
                )
                ->setNumeroResolution(
                    (string) $resolution->getNumero()
                )
                ->setExerciceComptabilisation(
                    $exerciceComptabilisation
                );

            $approbationService->approuver($approbation);

            $this->addFlash(
                'success',
                sprintf(
                    'L’approbation des comptes %s a été comptabilisée.',
                    $exercice->getNom()
                )
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                'warning',
                $e->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    #[Route(
        '/{id}/convocation/repartition/{coproprietaire}/pdf',
        name: 'app_assemblee_generale_repartition_individuelle_pdf',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function repartitionIndividuellePdf(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        SimulationRegularisationService $simulationRegularisationService,
        EcritureRepository $ecritureRepository,
        PdfService $pdfService
    ): Response {
        $donnees = $this->preparerRepartitionIndividuelle(
            $assembleeGenerale,
            $coproprietaire,
            $simulationRegularisationService,
            $ecritureRepository
        );

        $donnees['css'] = $pdfService->chargerCss();

        $html = $this->renderView(
            'assemblee_generale/pdf/repartition_individuelle.html.twig',
            $donnees
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="repartition-'
                    . $assembleeGenerale->getExercice()->getNom()
                    . '-'
                    . $coproprietaire->getId()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/{id}/vote-correspondance/{coproprietaire}/pdf',
        name: 'app_assemblee_generale_vote_correspondance_pdf',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function voteCorrespondancePdf(
        AssembleeGenerale $assembleeGenerale,
        PdfService $pdfService,
        Coproprietaire $coproprietaire,
        MandatCoproprieteRepository $mandatCoproprieteRepository,
    ): Response {
        $exercice = $assembleeGenerale->getExercice();

        $syndicMandat = $mandatCoproprieteRepository
            ->findSyndicAlaDate(
                $exercice->getCopropriete(),
                $assembleeGenerale->getDate()
            );

        $syndic = $syndicMandat?->getCoproprietaire();

        $dateLimiteVote = $assembleeGenerale
            ->getDate()
            ->modify('-4 days');

        $html = $this->renderView(
            'assemblee_generale/pdf/vote_correspondance.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $exercice,
                'coproprietaire' => $coproprietaire,
                'syndic' => $syndic,
                'date_limite_vote' => $dateLimiteVote,
                'css' => $pdfService->chargerCss(),
            ]
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="vote-correspondance-ag-'
                    . $assembleeGenerale->getExercice()->getNom()
                    . '.pdf"',
            ]
        );
    }
    #[Route(
        '/{id}/pouvoir/{coproprietaire}/pdf',
        name: 'app_assemblee_generale_pouvoir_pdf',
        requirements: [
            'id' => '\d+',
            'coproprietaire' => '\d+',
        ],
        methods: ['GET']
    )]
    public function pouvoirPdf(
        AssembleeGenerale $assembleeGenerale,
        Coproprietaire $coproprietaire,
        PdfService $pdfService
    ): Response {
        $exercice = $assembleeGenerale->getExercice();

        $html = $this->renderView(
            'assemblee_generale/pdf/pouvoir.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'exercice' => $exercice,
                'coproprietaire' => $coproprietaire,
                'css' => $pdfService->chargerCss(),
            ]
        );

        $dompdf = $pdfService->creer();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfService->ajouterPagination($dompdf);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                'inline; filename="pouvoir-ag-'
                    . $exercice->getNom()
                    . '-'
                    . $coproprietaire->getId()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/{id}/votes-correspondance',
        name: 'app_assemblee_generale_votes_correspondance',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function votesCorrespondance(
        AssembleeGenerale $assembleeGenerale
    ): Response {
        $votantsCorrespondance = [];

        foreach ($assembleeGenerale->getPresences() as $presence) {
            if (
                $presence->getStatut()
                === PresenceStatut::VOTE_CORRESPONDANCE
            ) {
                $votantsCorrespondance[] = $presence;
            }
        }

        return $this->render(
            'assemblee_generale/votes_correspondance.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'votants_correspondance' => $votantsCorrespondance,
            ]
        );
    }

    #[Route(
        '/presence/{id}/votes-correspondance',
        name: 'app_assemblee_generale_saisir_votes_correspondance',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function saisirVotesCorrespondance(
        PresenceAssembleeGenerale $presence,
        Request $request,
        VoteResolutionService $voteService,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            $presence->getStatut()
            !== PresenceStatut::VOTE_CORRESPONDANCE
        ) {
            throw $this->createNotFoundException(
                'Ce copropriétaire ne vote pas par correspondance.'
            );
        }

        $assembleeGenerale = $presence->getAssembleeGenerale();

        if ($this->estCloturee($assembleeGenerale)) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale est clôturée. Les votes par correspondance ne peuvent plus être modifiés.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $data = [
            'votes' => [],
        ];

        foreach ($assembleeGenerale->getResolutions() as $resolution) {

            if ($resolution->getMajorite() === null) {
                continue;
            }

            $choixExistant = null;

            foreach ($resolution->getVotes() as $vote) {

                if (
                    $vote->getTour() === 1
                    && $vote->getCoproprietaire()
                    === $presence->getCoproprietaire()
                ) {
                    $choixExistant = $vote->getChoix();
                    break;
                }
            }

            $data['votes'][] = [
                'resolution' => $resolution,
                'choix' => $choixExistant,
            ];
        }

        $form = $this->createForm(
            VotesCorrespondanceType::class,
            $data
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $donnees = $form->getData();

            foreach ($donnees['votes'] as $ligne) {

                $voteService->enregistrerVotesCorrespondance(
                    $ligne['resolution'],
                    $presence,
                    $ligne['choix']
                );
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Les votes par correspondance ont été enregistrés.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_votes_correspondance',
                [
                    'id' => $assembleeGenerale->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/saisie_votes_correspondance.html.twig',
            [
                'assemblee_generale' => $assembleeGenerale,
                'presence' => $presence,
                'form' => $form,
            ]
        );
    }

    private function calculerParticipation(
        AssembleeGenerale $assembleeGenerale
    ): array {
        $participation = [
            'presents' => ['nombre' => 0, 'voix' => 0],
            'representes' => ['nombre' => 0, 'voix' => 0],
            'correspondance' => ['nombre' => 0, 'voix' => 0],
            'absents' => ['nombre' => 0, 'voix' => 0],
        ];

        foreach ($assembleeGenerale->getPresences() as $presence) {

            $nombreVoix = $presence->getNombreVoix() ?? 0;

            switch ($presence->getStatut()) {

                case PresenceStatut::PRESENT:
                    $participation['presents']['nombre']++;
                    $participation['presents']['voix'] += $nombreVoix;
                    break;

                case PresenceStatut::REPRESENTE:
                    $participation['representes']['nombre']++;
                    $participation['representes']['voix'] += $nombreVoix;
                    break;

                case PresenceStatut::VOTE_CORRESPONDANCE:
                    $participation['correspondance']['nombre']++;
                    $participation['correspondance']['voix'] += $nombreVoix;
                    break;

                case PresenceStatut::ABSENT:
                    $participation['absents']['nombre']++;
                    $participation['absents']['voix'] += $nombreVoix;
                    break;
            }
        }

        return $participation;
    }

    #[Route(
        '/{id}/demarrer',
        name: 'app_assemblee_generale_demarrer',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function demarrer(
        AssembleeGenerale $assembleeGenerale,
        EntityManagerInterface $entityManager,
    ): Response {
        if (
            $assembleeGenerale->getStatut()
            !== AssembleeGeneraleStatut::PREPARATION
        ) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale ne peut pas être démarrée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        $assembleeGenerale->setStatut(
            AssembleeGeneraleStatut::EN_COURS
        );

        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’assemblée générale est maintenant en cours.'
        );

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    #[Route(
        '/{id}/cloturer',
        name: 'app_assemblee_generale_cloturer',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function cloturer(
        AssembleeGenerale $assembleeGenerale,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($assembleeGenerale->getStatut() !== AssembleeGeneraleStatut::EN_COURS) {
            $this->addFlash(
                'warning',
                'Cette assemblée générale ne peut pas être clôturée.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if ($assembleeGenerale->getHeureCloture() === null) {
            $assembleeGenerale->setHeureCloture(
                new \DateTimeImmutable()
            );
        }

        $form = $this->createForm(
            ClotureAssembleeGeneraleType::class,
            $assembleeGenerale
        );

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render(
                'assemblee_generale/cloturer.html.twig',
                [
                    'assemblee_generale' => $assembleeGenerale,
                    'form' => $form,
                ]
            );
        }

        if (
            !$assembleeGenerale->getPresidentSeance()
            || !$assembleeGenerale->getSecretaireSeance()
        ) {
            $this->addFlash(
                'danger',
                'Le président et le secrétaire de séance doivent être renseignés.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale_show',
                ['id' => $assembleeGenerale->getId()]
            );
        }

        if (!$form->isValid()) {
            return $this->render(
                'assemblee_generale/cloturer.html.twig',
                [
                    'assemblee_generale' => $assembleeGenerale,
                    'form' => $form,
                ]
            );
        }

        foreach ($assembleeGenerale->getResolutions() as $resolution) {

            // Une résolution sans majorité n'est pas soumise au vote.
            if ($resolution->getMajorite() === null) {
                continue;
            }

            if ($resolution->isSecondVoteNecessaire()) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        'La résolution n° %d nécessite encore un second vote.',
                        $resolution->getNumero()
                    )
                );

                return $this->redirectToRoute(
                    'app_assemblee_generale_show',
                    ['id' => $assembleeGenerale->getId()]
                );
            }

            if ($resolution->isAdoptee() === null) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        'La résolution n° %d n’a pas encore été votée.',
                        $resolution->getNumero()
                    )
                );

                return $this->redirectToRoute(
                    'app_assemblee_generale_show',
                    ['id' => $assembleeGenerale->getId()]
                );
            }
        }

        $assembleeGenerale->setStatut(
            AssembleeGeneraleStatut::CLOTUREE
        );

        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’assemblée générale a été clôturée.'
        );

        return $this->redirectToRoute(
            'app_assemblee_generale_show',
            ['id' => $assembleeGenerale->getId()]
        );
    }

    private function estCloturee(
        AssembleeGenerale $assembleeGenerale
    ): bool {
        return $assembleeGenerale->getStatut()
            === AssembleeGeneraleStatut::CLOTUREE;
    }
}
