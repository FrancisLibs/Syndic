<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Enum\ExerciceStatut;
use App\Form\ExerciceType;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CalculChargesReellesService;
use App\Service\ContexteExerciceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exercice')]
final class ExerciceController extends AbstractController
{
    #[Route(name: 'app_exercice_index', methods: ['GET'])]
    public function index(ExerciceRepository $exerciceRepository): Response
    {
        return $this->render(
            'exercice/index.html.twig',
            [
                'exercices' => $exerciceRepository->findAll(),
            ]
        );
    }

    #[Route('/new', name: 'app_exercice_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ContexteExerciceService $contexteExercice,
    ): Response {
        $exercice = new Exercice();
        $exercice->setNom('Exercice ' . (new \DateTime())->format('Y'));
        $exercice->setDateDebut(new \DateTimeImmutable('first day of January this year'));
        $exercice->setDateFin(new \DateTimeImmutable('last day of December this year'));
        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($exercice);
            $entityManager->flush();

            $contexteExercice->setExercice($exercice);

            return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'exercice/new.html.twig',
            [
                'exercice' => $exercice,
                'form' => $form->createView(),
            ]
        );
    }

    #[Route('/{id}', name: 'app_exercice_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('exercice/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_exercice_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'exercice/edit.html.twig',
            [
                'exercice' => $exercice,
                'form' => $form->createView(),
            ]
        );
    }

    #[Route('/{id}', name: 'app_exercice_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $exercice->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($exercice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/activer',
        name: 'app_exercice_activer',
        methods: ['POST']
    )]
    public function activer(
        Exercice $exercice,
        ExerciceRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {

        if ($exercice->getStatut() === ExerciceStatut::CLOTURE) {
            throw new \LogicException(
                'Impossible d\'activer un exercice clôturé'
            );
        }

        foreach (
            $repository->findAll()
            as $autreExercice
        ) {

            if (
                $autreExercice->getId() !== $exercice->getId()
            ) {
                $autreExercice->setActif(false);
            }
        }

        $exercice->setActif(true);

        $entityManager->flush();

        return $this->redirectToRoute(
            'app_exercice_index'
        );
    }

    #[Route(
        '/exercice/{id}/controle-charges',
        name: 'app_exercice_controle_charges'
    )]
    public function controleCharges(
        Exercice $exercice,
        CalculChargesReellesService $service
    ): Response {

        $lignes = [];

        foreach (
            $exercice->getCopropriete()->getLots() as $lot
        ) {

            $copro = $lot->getCoproprietaireActuel();

            if (!$copro) {
                continue;
            }

            $id = $copro->getId();

            if (isset($lignes[$id])) {
                continue;
            }

            $calcul = $service->calculer($copro, $exercice);

            $lignes[$id] = [
                'copro' => $copro,
                'appels' => $calcul['appels'],
                'charges' => $calcul['charges'],
                'ecart' => $calcul['ecart'],
            ];
        }

        return $this->render(
            'exercice/controle_charges.html.twig',
            [
                'exercice' => $exercice,
                'lignes' => $lignes,
            ]
        );
    }

    #[Route(
        '/changer-exercice',
        name: 'app_changer_exercice',
        methods: ['POST']
    )]
    public function changerExercice(
        Request $request,
        ExerciceRepository $exerciceRepository,
        ContexteExerciceService $contexteExercice
    ): Response {
        $exerciceId = $request->request->getInt('exercice_id');

        $exercice = $exerciceRepository->find($exerciceId);

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Exercice introuvable.'
            );
        }

        $contexteExercice->setExercice($exercice);

        return $this->redirect(
            $request->headers->get('referer')
                ?? $this->generateUrl('app_exercice_index')
        );
    }
}
