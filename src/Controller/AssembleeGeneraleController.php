<?php

namespace App\Controller;

use App\Entity\ApprobationComptes;
use App\Entity\Exercice;
use App\Form\ApprobationComptesType;
use App\Repository\ApprobationComptesRepository;
use App\Service\AssembleeGenerale\ApprobationComptesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assemblee-generale')]
final class AssembleeGeneraleController extends AbstractController
{
    #[Route(
        '/exercice/{id}',
        name: 'app_assemblee_generale',
        methods: ['GET', 'POST']
    )]
    public function index(
        Exercice $exercice,
        Request $request,
        ApprobationComptesService $approbationComptesService,
        ApprobationComptesRepository $approbationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $approbation =
            $approbationComptesService
            ->preparerApprobation($exercice);

        $approbationEnregistree =
            $approbationRepository
            ->findOneBy([
                'exercice' => $exercice,
            ]);

        if (!$approbationEnregistree) {
            $approbationEnregistree =
                new ApprobationComptes();

            $approbationEnregistree
                ->setExercice($exercice);
        }

        $form = $this->createForm(
            ApprobationComptesType::class,
            $approbationEnregistree
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $entityManager->persist(
                $approbationEnregistree
            );

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Les informations de l’Assemblée Générale ont été enregistrées.'
            );

            return $this->redirectToRoute(
                'app_assemblee_generale',
                [
                    'id' => $exercice->getId(),
                ]
            );
        }

        return $this->render(
            'assemblee_generale/index.html.twig',
            [
                'exercice' => $exercice,
                'approbation' => $approbation,
                'approbationEnregistree' => $approbationEnregistree,
                'form' => $form->createView(),
            ]
        );
    }

    #[Route(
        '/approbation/{id}/approuver',
        name: 'app_assemblee_generale_approuver',
        methods: ['POST']
    )]
    public function approuver(
        ApprobationComptes $approbation,
        ApprobationComptesService $service
    ): Response {
        $exercice = $approbation->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice n’est associé à cette approbation.'
            );
        }

        try {
            $service->approuver($approbation);

            $this->addFlash(
                'success',
                'Les comptes ont été approuvés et les écritures comptables ont été générées.'
            );
        } catch (\LogicException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        } catch (\Throwable $exception) {
            $this->addFlash(
                'danger',
                'Une erreur est survenue pendant l’approbation des comptes.'
            );
        }

        return $this->redirectToRoute(
            'app_assemblee_generale',
            [
                'id' => $exercice->getId(),
            ]
        );
    }
}
