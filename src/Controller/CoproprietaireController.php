<?php

namespace App\Controller;

use App\Entity\Coproprietaire;
use App\Enum\OperationType;
use App\Form\CoproprietaireType;
use App\Repository\CoproprietaireRepository;
use App\Repository\EcritureRepository;
use App\Repository\OperationRepository;
use App\Repository\RepartitionRepository;
use App\Service\CompteCoproprietaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/coproprietaire')]
final class CoproprietaireController extends AbstractController
{
    #[Route(name: 'app_coproprietaire_index', methods: ['GET'])]
    public function index(CoproprietaireRepository $coproprietaireRepository): Response
    {
        return $this->render('coproprietaire/index.html.twig', [
            'coproprietaires' => $coproprietaireRepository->findAll(),
        ]);
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

    #[Route('/{id}', name: 'app_coproprietaire_show', methods: ['GET'])]
    public function show(Coproprietaire $coproprietaire): Response
    {
        return $this->render(
            'coproprietaire/show.html.twig',
            [
                'coproprietaire' => $coproprietaire,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_coproprietaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Coproprietaire $coproprietaire, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/{id}', name: 'app_coproprietaire_delete', methods: ['POST'])]
    public function delete(Request $request, Coproprietaire $coproprietaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $coproprietaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($coproprietaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_coproprietaire_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/releve', name: 'app_copro_releve', methods: ['GET'])]
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

        return $this->render('coproprietaire/releve.html.twig', [
            'copro' => $copro,
            'mouvements' => $mouvements,
            'totalCharges' => $totalCharges,
            'totalPaiements' => $totalPaiements,
            'solde' => $solde,
        ]);
    }

    #[Route(
        '/{id}/etat-compte',
        name: 'app_coproprietaire_etat_compte',
        methods: ['GET']
    )]
    public function etatCompte(
        Coproprietaire $copro,
        EcritureRepository $ecritureRepo
    ): Response {

        $compte = $copro->getCompte();

        if (!$compte) {

            throw $this->createNotFoundException(
                'Aucun compte associé à ce copropriétaire'
            );
        }

        // =========================
        // Écritures du compte
        // =========================

        $ecritures = $ecritureRepo->findBy(
            ['compte' => $compte],
            ['date' => 'ASC', 'id' => 'ASC']
        );

        $mouvements = [];

        $solde = 0;

        // =========================
        // Construction mouvements
        // =========================

        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            $debit = (float) $ecriture->getDebit();

            $credit = (float) $ecriture->getCredit();

            $solde += $debit;

            $solde -= $credit;

            $mouvements[] = [
                'exercice' => $ecriture->getExercice()->getNom(),

                'date' => $ecriture->getDate(),

                'piece' => $operation?->getPiece(),

                'libelle' => $operation?->getLibelle(),

                'type' => $operation?->getType()?->value,

                'debit' => $debit,

                'credit' => $credit,

                'solde' => $solde,
            ];
        }

        // =========================
        // Totaux
        // =========================

        $totalDebit = array_sum(
            array_column($mouvements, 'debit')
        );

        $totalCredit = array_sum(
            array_column($mouvements, 'credit')
        );

        // =========================
        // Render
        // =========================

        return $this->render(
            'coproprietaire/compte.html.twig',
            [
                'copro' => $copro,

                'compte' => $compte,

                'mvt' => $mouvements,

                'totalDebit' => $totalDebit,

                'totalCredit' => $totalCredit,

                'solde' => $solde,
            ]
        );
    }
}
