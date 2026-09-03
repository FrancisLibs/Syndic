<?php

namespace App\Controller;

use App\Service\ContexteExerciceService;
use App\Service\PdfService;
use App\Service\EtatRepartitionChargesService;
use App\Service\EtatCompteCoproprietaireService;
use App\Service\Eau\EauService;
use App\Service\Cloture\ClotureExerciceService;
use App\Service\AssembleeGenerale\ApprobationComptesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Enum\OperationStatut;
use App\Repository\EcritureRepository;
use App\Repository\CoproprietaireRepository;
use App\Repository\CompteRepository;



final class DocumentExerciceController extends AbstractController
{
    #[Route(
        '/documents/exercice',
        name: 'app_document_exercice_index',
        methods: ['GET']
    )]
    public function index(
        ContexteExerciceService $contexteExercice
    ): Response {
        $exercice = $contexteExercice->getExercice();

        return $this->render('document_exercice/index.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route(
        '/documents/exercice/balance/pdf',
        name: 'app_document_exercice_balance_pdf',
        methods: ['GET']
    )]
    public function balancePdf(
        ContexteExerciceService $contexteExercice,
        EcritureRepository $ecritureRepository,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        $ecritures = $ecritureRepository->findBy(
            ['exercice' => $exercice]
        );

        $balance = [];

        foreach ($ecritures as $ecriture) {

            if (
                $ecriture->getOperation()->getStatut()
                !== OperationStatut::VALIDE
            ) {
                continue;
            }

            $compte = $ecriture->getCompte();
            $numero = $compte->getNumero();

            if (!isset($balance[$numero])) {
                $balance[$numero] = [
                    'numero' => $numero,
                    'libelle' => $compte->getLibelle(),
                    'debit' => 0.0,
                    'credit' => 0.0,
                ];
            }

            $balance[$numero]['debit'] +=
                (float) $ecriture->getDebit();

            $balance[$numero]['credit'] +=
                (float) $ecriture->getCredit();
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalSoldeDebiteur = 0.0;
        $totalSoldeCrediteur = 0.0;

        foreach ($balance as &$ligne) {

            $solde = round(
                $ligne['debit'] - $ligne['credit'],
                2
            );

            $ligne['soldeDebiteur'] = 0.0;
            $ligne['soldeCrediteur'] = 0.0;

            if ($solde > 0) {
                $ligne['soldeDebiteur'] = $solde;
            } elseif ($solde < 0) {
                $ligne['soldeCrediteur'] = abs($solde);
            }

            $totalDebit += $ligne['debit'];
            $totalCredit += $ligne['credit'];

            $totalSoldeDebiteur += $ligne['soldeDebiteur'];
            $totalSoldeCrediteur += $ligne['soldeCrediteur'];
        }

        unset($ligne);

        $equilibree =
            abs($totalDebit - $totalCredit) < 0.01;

        ksort($balance);

        $css = $pdfService->chargerCss();

        $html = $this->renderView(
            'document_exercice/pdf/balance.html.twig',
            [
                'exercice' => $exercice,
                'balance' => $balance,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'totalSoldeDebiteur' => $totalSoldeDebiteur,
                'totalSoldeCrediteur' => $totalSoldeCrediteur,
                'equilibree' => $equilibree,
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
                'inline; filename="balance-' . $exercice->getNom() . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/grand-livre/pdf',
        name: 'app_document_exercice_grand_livre_pdf',
        methods: ['GET']
    )]
    public function grandLivrePdf(
        ContexteExerciceService $contexteExercice,
        CompteRepository $compteRepository,
        EcritureRepository $ecritureRepository,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice actif trouvé'
            );
        }

        $comptes = $compteRepository->findBy(
            [],
            ['numero' => 'ASC']
        );

        $grandLivre = [];

        foreach ($comptes as $compte) {

            $ecritures = $ecritureRepository->findBy(
                [
                    'exercice' => $exercice,
                    'compte' => $compte,
                ],
                [
                    'date' => 'ASC',
                    'id' => 'ASC',
                ]
            );

            $mouvements = [];
            $solde = 0.0;

            foreach ($ecritures as $ecriture) {

                $operation = $ecriture->getOperation();

                if (
                    $operation->getStatut()
                    !== OperationStatut::VALIDE
                ) {
                    continue;
                }

                $debit = (float) $ecriture->getDebit();
                $credit = (float) $ecriture->getCredit();

                $solde += $debit - $credit;

                $mouvements[] = [
                    'date' => $operation->getDate(),
                    'libelle' => $operation->getLibelle(),
                    'debit' => $debit,
                    'credit' => $credit,
                    'solde' => round($solde, 2),
                ];
            }

            if ($mouvements === []) {
                continue;
            }

            $grandLivre[] = [
                'compte' => $compte,
                'mouvements' => $mouvements,
                'totalDebit' => array_sum(
                    array_column($mouvements, 'debit')
                ),
                'totalCredit' => array_sum(
                    array_column($mouvements, 'credit')
                ),
                'solde' => round($solde, 2),
            ];
        }

        $html = $this->renderView(
            'document_exercice/pdf/grand_livre.html.twig',
            [
                'exercice' => $exercice,
                'grandLivre' => $grandLivre,
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
                'inline; filename="grand-livre-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/journal/pdf',
        name: 'app_document_exercice_journal_pdf',
        methods: ['GET']
    )]
    public function journalPdf(
        ContexteExerciceService $contexteExercice,
        EcritureRepository $ecritureRepository,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $ecritures = $ecritureRepository->findBy(
            ['exercice' => $exercice],
            ['id' => 'ASC']
        );

        $lignes = [];

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            if (
                $operation->getStatut()
                !== OperationStatut::VALIDE
            ) {
                continue;
            }

            $compte = $ecriture->getCompte();

            $debit = (float) $ecriture->getDebit();
            $credit = (float) $ecriture->getCredit();

            $lignes[] = [
                'date' => $operation->getDate(),
                'type' => $operation->getType()?->value,
                'libelle' => $operation->getLibelle(),
                'compte' => $compte->getNumero(),
                'compteLibelle' => $compte->getLibelle(),
                'debit' => $debit,
                'credit' => $credit,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $html = $this->renderView(
            'document_exercice/pdf/journal.html.twig',
            [
                'exercice' => $exercice,
                'lignes' => $lignes,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
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
                'inline; filename="journal-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/comptes-coproprietaires/pdf',
        name: 'app_document_exercice_comptes_coproprietaires_pdf',
        methods: ['GET']
    )]
    public function comptesCoproprietairesPdf(
        ContexteExerciceService $contexteExercice,
        CoproprietaireRepository $coproprietaireRepository,
        EtatCompteCoproprietaireService $etatCompteService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $comptesCoproprietaires = [];

        foreach (
            $coproprietaireRepository->findAll()
            as $coproprietaire
        ) {
            $etat = $etatCompteService->construire(
                $coproprietaire,
                $exercice
            );

            if ($etat !== null) {
                $comptesCoproprietaires[] = $etat;
            }
        }

        $html = $this->renderView(
            'document_exercice/pdf/comptes_coproprietaires.html.twig',
            [
                'exercice' => $exercice,
                'comptesCoproprietaires' =>
                $comptesCoproprietaires,
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
                'inline; filename="comptes-coproprietaires-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/repartition-charges/pdf',
        name: 'app_document_exercice_repartition_charges_pdf',
        methods: ['GET']
    )]
    public function repartitionChargesPdf(
        ContexteExerciceService $contexteExercice,
        EtatRepartitionChargesService $etatRepartitionChargesService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $etat = $etatRepartitionChargesService
            ->construire($exercice);

        $html = $this->renderView(
            'document_exercice/pdf/repartition_charges.html.twig',
            [
                'exercice' => $exercice,
                'css' => $pdfService->chargerCss(),
                ...$etat,
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
                'inline; filename="repartition-charges-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/comptes-coproprietaires',
        name: 'app_document_exercice_comptes_coproprietaires',
        methods: ['GET']
    )]
    public function comptesCoproprietaires(
        ContexteExerciceService $contexteExercice,
        CoproprietaireRepository $coproprietaireRepository,
        EtatCompteCoproprietaireService $etatCompteService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $comptesCoproprietaires = [];

        foreach (
            $coproprietaireRepository->findAll()
            as $coproprietaire
        ) {
            $etat = $etatCompteService->construire(
                $coproprietaire,
                $exercice
            );

            if ($etat !== null) {
                $comptesCoproprietaires[] = $etat;
            }
        }

        return $this->render(
            'document_exercice/comptes_coproprietaires.html.twig',
            [
                'exercice' => $exercice,
                'comptesCoproprietaires' =>
                $comptesCoproprietaires,
            ]
        );
    }

    #[Route(
        '/documents/exercice/repartition-eau/pdf',
        name: 'app_document_exercice_repartition_eau_pdf',
        methods: ['GET']
    )]
    public function repartitionEauPdf(
        ContexteExerciceService $contexteExercice,
        EauService $eauService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        try {
            $calcul = $eauService->calculer($exercice);
        } catch (\DomainException $exception) {
            throw $this->createNotFoundException(
                $exception->getMessage()
            );
        }

        $html = $this->renderView(
            'document_exercice/pdf/repartition_eau.html.twig',
            [
                'exercice' => $exercice,
                'calcul' => $calcul,
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
                'inline; filename="repartition-eau-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/cloture-comptes-gestion/pdf',
        name: 'app_document_exercice_cloture_comptes_gestion_pdf',
        methods: ['GET']
    )]
    public function clotureComptesGestionPdf(
        ContexteExerciceService $contexteExercice,
        ClotureExerciceService $clotureExerciceService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $cloture = $clotureExerciceService
            ->getClotureComptesGestion($exercice);

        $html = $this->renderView(
            'document_exercice/pdf/cloture_comptes_gestion.html.twig',
            [
                'exercice' => $exercice,
                'cloture' => $cloture,
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
                'inline; filename="cloture-comptes-gestion-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/soldes-reportables/pdf',
        name: 'app_document_exercice_soldes_reportables_pdf',
        methods: ['GET']
    )]
    public function soldesReportablesPdf(
        ContexteExerciceService $contexteExercice,
        ClotureExerciceService $clotureExerciceService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $soldes = $clotureExerciceService
            ->calculerSoldesReportables($exercice);

        $html = $this->renderView(
            'document_exercice/pdf/soldes_reportables.html.twig',
            [
                'exercice' => $exercice,
                'soldes' => $soldes,
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
                'inline; filename="soldes-reportables-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/a-nouveaux/pdf',
        name: 'app_document_exercice_anouveaux_pdf',
        methods: ['GET']
    )]
    public function anouveauxPdf(
        ContexteExerciceService $contexteExercice,
        ClotureExerciceService $clotureExerciceService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $anouveaux = $clotureExerciceService
            ->getANouveaux($exercice);

        $html = $this->renderView(
            'document_exercice/pdf/anouveaux.html.twig',
            [
                'exercice' => $exercice,
                'anouveaux' => $anouveaux,
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
                'inline; filename="a-nouveaux-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/approbation-comptes/pdf',
        name: 'app_document_exercice_approbation_comptes_pdf',
        methods: ['GET']
    )]
    public function approbationComptesPdf(
        ContexteExerciceService $contexteExercice,
        ApprobationComptesService $approbationComptesService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $simulation = $approbationComptesService
            ->preparerApprobation($exercice);

        $html = $this->renderView(
            'document_exercice/pdf/approbation_comptes.html.twig',
            [
                'exercice' => $exercice,
                'simulation' => $simulation,
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
                'inline; filename="approbation-comptes-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }

    #[Route(
        '/documents/exercice/dossier-cloture/pdf',
        name: 'app_document_exercice_dossier_cloture_pdf',
        methods: ['GET']
    )]
    public function dossierCloturePdf(
        ContexteExerciceService $contexteExercice,
        EtatRepartitionChargesService $etatRepartitionChargesService,
        EauService $eauService,
        ClotureExerciceService $clotureExerciceService,
        ApprobationComptesService $approbationComptesService,
        PdfService $pdfService,
    ): Response {
        $exercice = $contexteExercice->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        // =========================
        // Répartition des charges
        // =========================

        $etatRepartitionCharges =
            $etatRepartitionChargesService->construire($exercice);

        // =========================
        // Répartition de l'eau
        // =========================

        $calculEau = null;

        try {
            $calculEau = $eauService->calculer($exercice);
        } catch (\DomainException) {
            // Pas de répartition d'eau disponible :
            // la section ne sera pas intégrée au dossier.
        }

        // =========================
        // Clôture
        // =========================

        $cloture = $clotureExerciceService
            ->getClotureComptesGestion($exercice);

        $soldes = $clotureExerciceService
            ->calculerSoldesReportables($exercice);

        $anouveaux = $clotureExerciceService
            ->getANouveaux($exercice);

        // =========================
        // Approbation
        // =========================

        $simulation = $approbationComptesService
            ->preparerApprobation($exercice);

        // =========================
        // PDF
        // =========================

        $html = $this->renderView(
            'document_exercice/pdf/dossier_cloture.html.twig',
            [
                'exercice' => $exercice,

                ...$etatRepartitionCharges,

                'calcul' => $calculEau,
                'calculEau' => $calculEau,

                'cloture' => $cloture,
                'soldes' => $soldes,
                'anouveaux' => $anouveaux,
                'simulation' => $simulation,

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
                'inline; filename="dossier-cloture-'
                    . $exercice->getNom()
                    . '.pdf"',
            ]
        );
    }
}
