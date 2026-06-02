<?php

namespace App\Controller;

use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Form\PaiementFournisseurType;
use App\Repository\CompteRepository;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaiementFournisseurController extends AbstractController
{
    #[Route('/paiement/fournisseur', name: 'app_paiement_fournisseur')]
    public function index(): Response
    {
        return $this->render(
            'paiement_fournisseur/index.html.twig',
            [
                'controller_name' => 'PaiementFournisseurController',
            ]
        );
    }

    #[Route(
        '/paiement-fournisseur/new',
        name: 'app_paiement_fournisseur_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CompteRepository $compteRepo,
        ExerciceRepository $exerciceRepository
    ): Response {

        $operation = new Operation();
        $operation->setDate(new \DateTimeImmutable());

        $form = $this->createForm(PaiementFournisseurType::class, $operation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // =========================
            // Données formulaire
            // =========================

            $montant = $form->get('montant')->getData();
            $date = $operation->getDate();

            // =========================
            // Comptes comptables
            // =========================
            $compteFournisseur = $operation->getFournisseur()->getCompte();

            $compteBanque = $compteRepo->findOneBy(
                [
                    'numero' => '512'
                ]
            );

            if (!$compteBanque) {
                throw new \Exception(
                    'Compte banque 512 introuvable.'
                );
            }

            // =========================
            // Opération
            // =========================

            $operation->setType(
                OperationType::PAIEMENT_FOURNISSEUR
            );

            $entityManager->persist($operation);

            // =========================
            // Écriture débit fournisseur
            // =========================
            $exercice = $exerciceRepository->findOneBy(
                ['statut' => 'ouvert'],
            );
            if (!$exercice) {
                throw new \Exception(
                    "Aucun exercice ouvert trouvé. Veuillez en créer un."
                );
            }

            $ecriture1 = new Ecriture();
            $ecriture1->setOperation($operation)
                ->setCompte($compteFournisseur)
                ->setDebit($montant)
                ->setCredit(0)
                ->setExercice($exercice)
                ->setDate($date);
            $entityManager->persist($ecriture1);

            // =========================
            // Écriture crédit banque
            // =========================
            $ecriture2 = new Ecriture();

            $ecriture2->setOperation($operation)
                ->setCompte($compteBanque)
                ->setDebit(0)
                ->setCredit($montant)
                ->setExercice($exercice)
                ->setDate($date);

            $entityManager->persist($ecriture2);

            // =========================
            // Sauvegarde
            // =========================
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_facture_index'
            );
        }

        return $this->render(
            'paiement_fournisseur/new.html.twig',
            [
                'form' => $form,
            ]
        );
    }
}
