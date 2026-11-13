<?php

namespace App\Controller;

use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;
use App\Repository\RapprochementBancaireRepository;
use App\Service\ContexteExerciceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Ecriture;
use App\Entity\RapprochementBancaire;
use App\Enum\OperationStatut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/rapprochement-bancaire')]
final class RapprochementBancaireController extends AbstractController
{
    #[Route(
        '/',
        name: 'app_rapprochement_bancaire_index',
        methods: ['GET']
    )]
    public function index(
        ContexteExerciceService $contexteExerciceService,
        CompteRepository $compteRepository,
        EcritureRepository $ecritureRepository,
        RapprochementBancaireRepository $rapprochementRepository
    ): Response {

        $exercice = $contexteExerciceService->getExercice();

        if ($exercice === null) {
            throw $this->createNotFoundException(
                'Aucun exercice comptable disponible.'
            );
        }

        $compte = $compteRepository->findOneBy([
            'numero' => '512000',
        ]);

        if ($compte === null) {
            throw $this->createNotFoundException(
                'Le compte bancaire 512000 est introuvable.'
            );
        }

        $ecritures = $ecritureRepository->findEcrituresBancaires(
            $compte,
            $exercice,
        );


        // Solde comptable du compte 512000
        // à la fin de l'exercice sélectionné.
        // Comprend les à-nouveaux et les mouvements validés.
        $soldeComptable = $ecritureRepository->calculerSoldeCompte(
            $exercice,
            $compte
        );


        $rapprochements = $rapprochementRepository->findBy([
            'ecriture' => $ecritures,
        ]);

        $rapprochementsParEcriture = [];

        foreach ($rapprochements as $rapprochement) {
            $rapprochementsParEcriture[
                $rapprochement->getEcriture()->getId()
            ] = $rapprochement;
        }

        $soldePointe = (float) $soldeComptable;
        $nombreNonPointees = 0;

        foreach ($ecritures as $ecriture) {
            $id = $ecriture->getId();

            if (!isset($rapprochementsParEcriture[$id])) {
                // Retirer du solde les mouvements non pointés
                $soldePointe -= (float) $ecriture->getDebit();
                $soldePointe += (float) $ecriture->getCredit();

                $nombreNonPointees++;
            }
        }

        return $this->render(
            'rapprochement_bancaire/index.html.twig',
            [
                'exercice' => $exercice,
                'compte' => $compte,
                'ecritures' => $ecritures,
                'rapprochements' => $rapprochementsParEcriture,
                'soldeComptable' => $soldeComptable,
                'soldePointe' => $soldePointe,
                'nombreNonPointees' => $nombreNonPointees,
            ]
        );
    }


    #[Route(
        '/{id}/pointer',
        name: 'app_rapprochement_bancaire_pointer',
        methods: ['POST']
    )]
    public function pointer(
        Ecriture $ecriture,
        Request $request,
        RapprochementBancaireRepository $rapprochementRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'pointer-bancaire-' . $ecriture->getId(),
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        if (
            $ecriture->getCompte()?->getNumero() !== '512000'
            || $ecriture->getOperation()?->getStatut() !== OperationStatut::VALIDE
            || $ecriture->getDate() < new \DateTimeImmutable('2026-01-01')
            || $ecriture->getOperation()?->getType()->value === 'a_nouveau'
        ) {
            throw $this->createAccessDeniedException(
                'Cette écriture ne peut pas être pointée.'
            );
        }

        if ($rapprochementRepository->findOneBy(['ecriture' => $ecriture])) {
            $this->addFlash('warning', 'Cette écriture est déjà pointée.');

            return $this->redirectToRoute('app_rapprochement_bancaire_index');
        }

        $dateSaisie = $request->request->getString('date_bancaire');

        $dateBancaire = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dateSaisie
        );

        if (
            !$dateBancaire
            || $dateBancaire->format('Y-m-d') !== $dateSaisie
        ) {
            $this->addFlash('danger', 'Date bancaire invalide.');

            return $this->redirectToRoute('app_rapprochement_bancaire_index');
        }

        $rapprochement = new RapprochementBancaire();

        $rapprochement
            ->setEcriture($ecriture)
            ->setDateRapprochement($dateBancaire)
            ->setReferenceReleve(
                trim($request->request->getString('reference_releve')) ?: null
            );

        $entityManager->persist($rapprochement);
        $entityManager->flush();

        $this->addFlash('success', 'Écriture bancaire pointée.');

        return $this->redirectToRoute('app_rapprochement_bancaire_index');
    }


    #[Route(
        '/{id}/annuler',
        name: 'app_rapprochement_bancaire_annuler',
        methods: ['POST']
    )]
    public function annuler(
        Ecriture $ecriture,
        Request $request,
        RapprochementBancaireRepository $rapprochementRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'annuler-pointage-' . $ecriture->getId(),
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        if ($ecriture->getCompte()?->getNumero() !== '512000') {
            throw $this->createAccessDeniedException(
                'Cette écriture ne concerne pas le compte bancaire.'
            );
        }

        $rapprochement = $rapprochementRepository->findOneBy([
            'ecriture' => $ecriture
        ]);

        if ($rapprochement === null) {
            $this->addFlash(
                'warning',
                'Cette écriture n’est pas pointée.'
            );
        } else {
            $entityManager->remove($rapprochement);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le pointage bancaire a été annulé.'
            );
        }

        return $this->redirectToRoute(
            'app_rapprochement_bancaire_index'
        );
    }


}
