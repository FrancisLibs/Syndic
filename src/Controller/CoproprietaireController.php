<?php

namespace App\Controller;

use App\Enum\OperationStatut;
use App\Entity\Coproprietaire;
use App\Form\CoproprietaireType;
use App\Repository\CoproprietaireRepository;
use App\Repository\EcritureRepository;
use App\Repository\ExerciceRepository;
use App\Repository\RepartitionRepository;
use App\Service\CompteCoproprietaireService;
use App\Service\ContexteExerciceService;
use App\Service\EtatCompteCoproprietaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/coproprietaire')]
final class CoproprietaireController extends AbstractController
{
    #[Route(name: 'app_coproprietaire_index', methods: ['GET'])]
    public function index(
        CoproprietaireRepository $coproprietaireRepository
    ): Response {
        return $this->render(
            'coproprietaire/index.html.twig',
            [
                'coproprietaires' => $coproprietaireRepository->findAll(),
            ]
        );
    }

    #[Route('/new', name: 'app_coproprietaire_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CompteCoproprietaireService $compteCoproprietaireService,
    ): Response {
        $coproprietaire = new Coproprietaire();
        $form = $this->createForm(CoproprietaireType::class, $coproprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $compte =
                $compteCoproprietaireService
                ->creerCompte(
                    $coproprietaire->getNom()
                );

            $coproprietaire
                ->setCompte($compte);

            $entityManager->persist($coproprietaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_coproprietaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'coproprietaire/new.html.twig',
            [
                'coproprietaire' => $coproprietaire,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_coproprietaire_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(Coproprietaire $coproprietaire): Response
    {
        $totalCharges = 0;

        foreach ($coproprietaire->getRepartitions() as $repartition) {
            $totalCharges += (float) $repartition->getMontant();
        }

        return $this->render(
            'coproprietaire/show.html.twig',
            [
                'coproprietaire' => $coproprietaire,
            ]
        );
    }

    #[Route(
        '/{id}/edit',
        name: 'app_coproprietaire_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        Coproprietaire $coproprietaire,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CoproprietaireType::class, $coproprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_coproprietaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'coproprietaire/edit.html.twig',
            [
                'coproprietaire' => $coproprietaire,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_coproprietaire_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function delete(
        Request $request,
        Coproprietaire $coproprietaire,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $coproprietaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($coproprietaire);
            $entityManager->flush();
        }

        return
            $this->redirectToRoute(
                'app_coproprietaire_index',
                [],
                Response::HTTP_SEE_OTHER
            );
    }

    #[Route(
        '/{id}/releve',
        name: 'app_copro_releve',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function releve(
        Coproprietaire $copro,
        RepartitionRepository $repartitionRepo,
        EcritureRepository $ecritureRepo
    ): Response {

        $repartitions = $repartitionRepo->findBy(['coproprietaire' => $copro]);
        $ecritures = $ecritureRepo->findBy(['coproprietaire' => $copro]);

        $mouvements = [];

        // Charges
        foreach ($repartitions as $r) {
            $op = $r->getEcriture()->getOperation();

            if ($op->getStatut()->value !== 'VALIDE') {
                continue;
            }

            $mouvements[] = [
                'date' => $op->getDate(),
                'libelle' => $op->getLibelle(),
                'type' => 'Charge',
                'debit' => (float) $r->getMontant(),
                'credit' => 0,
            ];
        }

        // Paiements
        foreach ($ecritures as $e) {
            $op = $e->getOperation();

            if ($op->getStatut()->value !== 'VALIDE') {
                continue;
            }

            $mouvements[] = [
                'date' => $op->getDate(),
                'libelle' => $op->getLibelle(),
                'type' => 'Paiement',
                'debit' => 0,
                'credit' => (float) $e->getCredit(),
            ];
        }

        // Tri
        usort($mouvements, fn($a, $b) => $a['date'] <=> $b['date']);

        $solde = 0;

        foreach ($mouvements as &$mvt) {
            $solde += $mvt['debit'];
            $solde -= $mvt['credit'];

            $mvt['solde'] = $solde;
        }
        unset($mvt);

        $totalCharges = array_sum(array_column($mouvements, 'debit'));
        $totalPaiements = array_sum(array_column($mouvements, 'credit'));
        $solde = $totalCharges - $totalPaiements;

        return $this->render(
            'coproprietaire/releve.html.twig',
            [
                'copro' => $copro,
                'mouvements' => $mouvements,
                'totalCharges' => $totalCharges,
                'totalPaiements' => $totalPaiements,
                'solde' => $solde,
            ]
        );
    }

    #[Route(
        '/etat-compte/{copro}',
        name: 'app_coproprietaire_etat_compte',
        methods: ['GET']
    )]
    public function etatCompte(
        Coproprietaire $copro,
        ContexteExerciceService $contexteExerciceService,
        EtatCompteCoproprietaireService $etatCompteService,
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice actif trouvé'
            );
        }

        $etat = $etatCompteService->construire(
            $copro,
            $exercice
        );

        if ($etat === null) {
            throw $this->createNotFoundException(
                'Aucun compte associé à ce copropriétaire'
            );
        }

        return $this->render(
            'coproprietaire/compte.html.twig',
            [
                'copro' => $copro,
                'compte' => $etat['compte'],
                'exercice' => $exercice,
                'mvt' => $etat['mouvements'],
                'totalDebit' => $etat['totalDebit'],
                'totalCredit' => $etat['totalCredit'],
                'solde' => $etat['solde'],
            ]
        );
    }

    #[Route(
        '/{copro}/charges',
        name: 'app_coproprietaire_charges',
        methods: ['GET']
    )]
    public function charges(
        Coproprietaire $copro,
        ContexteExerciceService $contexteExerciceService,
    ): Response {

        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        if (!$exercice->estTermine()) {
            $this->addFlash(
                'warning',
                'La répartition des charges est disponible uniquement en fin d’exercice.'
            );

            return $this->redirectToRoute(
                'app_coproprietaire_index'
            );
        }

        // =========================
        // Charges réparties
        // =========================

        $totaux = [];
        $totalGeneral = 0.0;

        foreach ($copro->getRepartitions() as $repartition) {

            $ecriture = $repartition->getEcriture();

            if (
                !$ecriture
                || !$ecriture->getExercice()
                || $ecriture->getExercice()->getId() !== $exercice->getId()
            ) {
                continue;
            }

            $typeCharge = $ecriture
                ->getOperation()
                ?->getTypeCharge();

            $libelle = $typeCharge?->getNom()
                ?? 'Non classé';

            $montant = (float) $repartition->getMontant();

            if (!isset($totaux[$libelle])) {
                $totaux[$libelle] = 0.0;
            }

            $totaux[$libelle] += $montant;

            $totalGeneral += $montant;
        }

        ksort($totaux);

        // =========================
        // Appels de fonds
        // =========================

        $totalAppels = 0.0;

        foreach ($copro->getLigneAppelFonds() as $ligne) {

            $appel = $ligne->getAppelFond();

            if (!$appel) {
                continue;
            }

            $budget = $appel->getBudget();

            if (
                !$budget
                || !$budget->getExercice()
                || $budget->getExercice()->getId() !== $exercice->getId()
            ) {
                continue;
            }

            $totalAppels += (float) $ligne->getMontant();
        }

        // =========================
        // Régularisation
        // =========================

        $regularisation = $totalGeneral - $totalAppels;

        // =========================
        // Affichage
        // =========================

        return $this->render(
            'coproprietaire/charges.html.twig',
            [
                'copro' => $copro,
                'exercice' => $exercice,
                'totaux' => $totaux,
                'totalGeneral' => $totalGeneral,
                'totalAppels' => $totalAppels,
                'regularisation' => $regularisation,
            ]
        );
    }
}
