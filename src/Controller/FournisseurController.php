<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Fournisseur;
use App\Form\Fournisseur1Type;
use App\Repository\EcritureRepository;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fournisseur')]
final class FournisseurController extends AbstractController
{
    #[Route(name: 'app_fournisseur_index', methods: ['GET'])]
    public function index(FournisseurRepository $fournisseurRepository): Response
    {
        return $this->render('fournisseur/index.html.twig', [
            'fournisseurs' => $fournisseurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_fournisseur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $fournisseur = new Fournisseur();
        $form = $this->createForm(Fournisseur1Type::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($fournisseur);
            $em->flush();

            $fournisseurId = $fournisseur->getId();

            $compte = new Compte();
            $compte->setNumero(
                '401' . str_pad(
                    $fournisseurId,
                    3,
                    '0',
                    STR_PAD_LEFT
                )
            );
            $compte->setLibelle(
                'Fournisseur ' . $fournisseur->getNom()
            );
            $compte->setType(\App\Enum\CompteType::TIERS);
            $fournisseur->setCompte($compte);

            $em->persist($compte);
            $em->flush();

            return $this->redirectToRoute('app_fournisseur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'fournisseur/new.html.twig',
            [
                'fournisseur' => $fournisseur,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_fournisseur_show', methods: ['GET'])]
    public function show(Fournisseur $fournisseur): Response
    {
        return $this->render('fournisseur/show.html.twig', [
            'fournisseur' => $fournisseur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fournisseur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fournisseur $fournisseur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Fournisseur1Type::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fournisseur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fournisseur/edit.html.twig', [
            'fournisseur' => $fournisseur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fournisseur_delete', methods: ['POST'])]
    public function delete(Request $request, Fournisseur $fournisseur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $fournisseur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fournisseur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_fournisseur_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/compte',
        name: 'app_fournisseur_compte',
        methods: ['GET']
    )]
    public function compte(
        Fournisseur $fournisseur,
        EcritureRepository $ecritureRepo
    ): Response {

        // =========================
        // Compte fournisseur
        // =========================
        $compte = $fournisseur->getCompte();

        // =========================
        // Écritures du compte
        // =========================
        $ecritures = $ecritureRepo->findBy(
            ['compte' => $compte],
            ['id' => 'ASC']
        );

        $mouvements = [];

        // =========================
        // Construction mouvements
        // =========================
        foreach ($ecritures as $ecriture) {

            $operation = $ecriture->getOperation();

            $mouvements[] = [

                'date' => $operation->getDate(),

                'libelle' => $operation->getLibelle(),

                'debit' => (float) $ecriture->getDebit(),

                'credit' => (float) $ecriture->getCredit(),
            ];
        }

        // =========================
        // Tri par date
        // =========================
        usort(
            $mouvements,
            function ($a, $b) {
                return $a['date'] <=> $b['date'];
            }
        );

        // =========================
        // Solde cumulatif
        // =========================
        $solde = 0;

        foreach ($mouvements as &$mvt) {

            $solde += $mvt['credit'];
            $solde -= $mvt['debit'];

            $mvt['solde'] = $solde;
        }

        unset($mvt);

        // =========================
        // Totaux
        // =========================
        $totalDebit = array_sum(
            array_column($mouvements, 'debit')
        );

        $totalCredit = array_sum(
            array_column($mouvements, 'credit')
        );

        return $this->render(
            'fournisseur/compte.html.twig',
            [
                'fournisseur' => $fournisseur,
                'compte' => $compte,
                'mouvements' => $mouvements,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'solde' => $solde,
            ]
        );
    }
}
