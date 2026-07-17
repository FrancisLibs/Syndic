<?php

namespace App\Controller;

use App\Entity\CompteurEau;
use App\Entity\Lot;
use App\Entity\LotCoproprietaire;
use App\Form\LotType;
use App\Form\CompteurEauType;
use App\Repository\LotRepository;
use App\Service\LotOwnershipManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lot')]
final class LotController extends AbstractController
{
    #[Route(name: 'app_lot_index', methods: ['GET'])]
    public function index(LotRepository $lotRepository): Response
    {
        $lots = $lotRepository->findAll();
        $totalTantiemes = $lotRepository->getTotalTantiemes();

        return $this->render(
            'lot/index.html.twig',
            [
                'lots' => $lots,
                'totalTantiemes' => $totalTantiemes,
            ]
        );
    }

    #[Route('/new', name: 'app_lot_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $lot = new Lot();
        $form = $this->createForm(LotType::class, $lot);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ⚠️ IMPORTANT : récupérer le copropriétaire depuis le form
            $copro = $form->get('coproprietaire')->getData();

            if ($copro) {
                $relation = new LotCoproprietaire();
                $relation->setLot($lot);
                $relation->setCoproprietaire($copro);
                $relation->setPourcentage(100);
                $relation->setDateDebut(new \DateTimeImmutable());

                $lot->addLotCoproprietaire($relation);
            }

            $entityManager->persist($lot);
            $entityManager->flush();

            return $this->redirectToRoute('app_lot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'lot/new.html.twig',
            [
                'lot' => $lot,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_lot_show', methods: ['GET'])]
    public function show(Lot $lot): Response
    {
        return $this->render(
            'lot/show.html.twig',
            [
                'lot' => $lot,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_lot_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Lot $lot,
        EntityManagerInterface $entityManager,
        LotOwnershipManagerService $ownershipManager,
    ): Response {

        $form = $this->createForm(LotType::class, $lot);
        $currentCopro = $lot->getCoproprietaireActuel();
        if ($currentCopro) {
            $form->get('coproprietaire')->setData($currentCopro);
        }
        $form->get('dateChangement')->setData(new \DateTimeImmutable());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $copro = $form->get('coproprietaire')->getData();
            $datechgt = $form->get('dateChangement')->getData();

            if ($copro) {
                $ownershipManager->changeOwner($lot, $copro, $datechgt);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_lot_index');
        }

        return $this->render(
            'lot/edit.html.twig',
            [
                'lot' => $lot,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_lot_delete', methods: ['POST'])]
    public function delete(Request $request, Lot $lot, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lot->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($lot);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_lot_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/compteur',
        name: 'app_lot_compteur',
        methods: ['GET', 'POST']
    )]
    public function compteur(
        Lot $lot,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $compteur = $lot->getCompteurEau();

        if (!$compteur) {
            $compteur = new CompteurEau();
            $compteur->setLot($lot);
        }

        $form = $this->createForm(
            CompteurEauType::class,
            $compteur,
            [
                'avec_lot' => false,
            ]
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {

            $entityManager->persist($compteur);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le compteur a été enregistré.'
            );

            return $this->redirectToRoute(
                'app_lot_show',
                [
                    'id' => $lot->getId(),
                ]
            );
        }

        return $this->render(
            'lot/compteur.html.twig',
            [
                'lot' => $lot,
                'form' => $form,
                'compteur' => $compteur,
            ]
        );
    }
}
