<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\ExerciceRepository;
use App\Repository\PaiementRepository;
use App\Repository\CoproprietaireRepository;
use App\Repository\LigneAppelFondRepository;
use App\Repository\Travaux\ChantierRepository;
use App\Service\AffectationPaiementService;
use App\Service\GenerationPaiementService;
use App\Service\ContexteExerciceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/paiement')]
final class PaiementController
extends AbstractController
{
    #[Route(
        '/index',
        name: 'app_paiement_index',
        methods: ['GET']
    )]
    public function index(
        PaiementRepository $paiementRepository,
        ContexteExerciceService $contexteExerciceService
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $paiements = $paiementRepository
            ->findPaiementsValidesByExercice($exercice);

        return $this->render(
            'paiement/index.html.twig',
            [
                'paiements' => $paiements,
                'exercice' => $exercice,
            ]
        );
    }

    #[Route(
        '/soldes/{id}',
        name: 'app_paiement_soldes',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function soldes(
        int $id,
        CoproprietaireRepository $coproprietaireRepository,
        LigneAppelFondRepository $ligneAppelFondRepository,
        ChantierRepository $chantierRepository,
        ExerciceRepository $exerciceRepository
    ): JsonResponse {

        $exercice = $exerciceRepository->findActif();

        if (!$exercice) {
            return $this->json([
                'erreur' => 'Aucun exercice actif.'
            ], 404);
        }

        $coproprietaire = $coproprietaireRepository->find($id);

        if (!$coproprietaire) {
            return $this->json([
                'erreur' => 'Copropriétaire introuvable.'
            ], 404);
        }

        // ============================
        // Charges courantes
        // ============================

        $lignesCharges = $ligneAppelFondRepository
            ->findLignesChargesARegler(
                $coproprietaire,
                $exercice
            );

        $chargesCentimes = 0;

        foreach ($lignesCharges as $ligne) {
            $chargesCentimes += max(
                0,
                (int) round($ligne->getResteAPayer() * 100)
            );
        }

        // ============================
        // Chantiers non clôturés
        // ============================

        $chantiersActifs = $chantierRepository->findBy([
            'copropriete' => $exercice->getCopropriete(),
            'cloture' => false,
        ]);

        $chantiers = [];

        foreach ($chantiersActifs as $chantier) {

            $lignesChantier = $ligneAppelFondRepository
                ->findLignesChantierARegler(
                    $coproprietaire,
                    $chantier
                );

            $totalCentimes = 0;

            foreach ($lignesChantier as $ligne) {
                $totalCentimes += max(
                    0,
                    (int) round($ligne->getResteAPayer() * 100)
                );
            }

            $chantiers[$chantier->getId()] = $totalCentimes / 100;
        }

        return $this->json([
            'charges' => $chargesCentimes / 100,
            'chantiers' => $chantiers,
        ]);
    }


    #[Route(
        '/new',
        name: 'app_paiement_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        GenerationPaiementService $generationService,
        AffectationPaiementService $affectationService,
        ExerciceRepository $exerciceRepository,
        \App\Repository\Travaux\ChantierRepository $chantierRepository
    ): Response {

        $exercice = $exerciceRepository->findActif();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice actif trouvé.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’enregistrer un paiement sur un exercice clôturé.'
            );
        }

        $chantiers = $chantierRepository->findBy([
            'copropriete' => $exercice->getCopropriete(),
            'cloture' => false,
        ]);

        $paiement = new Paiement();

        $paiement
            ->setExercice($exercice)
            ->setDatePaiement(new \DateTimeImmutable())
            ->setPiece('Relevé bancaire : RB');

        $form = $this->createForm(
            PaiementType::class,
            $paiement
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $paiement->setExercice($exercice);

            $montantCharges = $request->request->all('ventilation');
            $charges = $montantCharges['charges'] ?? '0';

            $montantsChantiers = $request->request->all('chantiers');

            try {
                $entityManager->wrapInTransaction(
                    function () use (
                        $entityManager,
                        $paiement,
                        $generationService,
                        $affectationService,
                        $charges,
                        $montantsChantiers
                    ): void {

                        $entityManager->persist($paiement);
                        $entityManager->flush();

                        $generationService->generer($paiement);

                        $affectationService->affecter(
                            $paiement,
                            (float) str_replace(',', '.', $charges),
                            $montantsChantiers
                        );
                    }
                );

                $this->addFlash(
                    'success',
                    'Le paiement et sa ventilation ont été enregistrés.'
                );

                return $this->redirectToRoute(
                    'app_paiement_show',
                    ['id' => $paiement->getId()]
                );
            } catch (\LogicException $e) {

                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render(
            'paiement/new.html.twig',
            [
                'exercice' => $exercice,
                'paiement' => $paiement,
                'form' => $form,
                'chantiers' => $chantiers,
            ]
        );
    }

    #[Route(
        'show/{id}',
        name: 'app_paiement_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        Paiement $paiement
    ): Response {

        return $this->render(
            'paiement/show.html.twig',
            [
                'paiement' => $paiement,
            ]
        );
    }
}
