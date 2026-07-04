<?php

namespace App\Controller;

use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use App\Enum\OperationStatut;
use App\Form\OperationFormType;
use App\Repository\ExerciceRepository;
use App\Repository\OperationRepository;
use App\Repository\LotRepository;
use App\Service\RepartitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OperationController extends AbstractController
{
    // src/Controller/OperationController.php

    #[Route('/operation', name: 'app_operation_index', methods: ['GET'])]
    public function index(
        Request $request,
        OperationRepository $operationRepository,
        ExerciceRepository $exerciceRepository
    ): Response {

        // 1. On récupère tous les exercices pour alimenter le menu déroulant de la vue
        $exercices = $exerciceRepository->findBy([], ['dateDebut' => 'DESC']);

        // 2. On détermine l'exercice à afficher
        $exerciceId = $request->query->get('exercice');
        $exerciceActuel = null;

        if ($exerciceId) {
            $exerciceActuel = $exerciceRepository->find($exerciceId);
        }

        // Si aucun exercice n'est sélectionné ou trouvé, on cherche l'exercice "Ouvert" ou le plus récent
        if (!$exerciceActuel) {
            $exerciceActuel = $exerciceRepository->findOneBy(['actif' => true])
                ?? $exerciceRepository->findOneBy([], ['dateDebut' => 'DESC']);
        }

        // 3. On récupère les opérations filtrées par les dates de cet exercice
        $operations = [];
        if ($exerciceActuel) {
            $operations = $operationRepository->findOperationsByExercice($exerciceActuel);
        }

        return $this->render('operation/index.html.twig', [
            'operations' => $operations,
            'exerciceActuel' => $exerciceActuel,
            'exercices' => $exercices,
        ]);
    }

    #[Route('/operation/{id}', name: 'app_operation_show')]
    public function show(Operation $operation): Response
    {
        // On cherche l'écriture qui porte le débit (la charge)
        // car c'est elle qui est liée aux répartitions
        $ecritureCharge = null;
        foreach ($operation->getEcritures() as $ecriture) {
            if ($ecriture->getDebit() > 0) {
                $ecritureCharge = $ecriture;
                break;
            }
        }

        return $this->render('operation/show.html.twig', [
            'operation' => $operation,
            'ecriture' => $ecritureCharge,
        ]);
    }

    #[Route('/operation/charge/new', name: 'operation_charge_new')]
    public function newCharge(
        Request $request,
        EntityManagerInterface $em,
        LotRepository $lotRepository,
        RepartitionService $repartitionService,
        ExerciceRepository $exerciceRepository
    ) {
        $operation = new Operation();
        $operation->setDate(new \DateTimeImmutable());
        $operation->setType(OperationType::CHARGE);
        $operation->setLibelle('');

        $form = $this->createForm(OperationFormType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $montant = $form->get('montant')->getData();
            $compteCharge = $form->get('compte')->getData();
            // sécurisation
            if ($operation->getType() !== OperationType::CHARGE) {
                throw new \LogicException('Type invalide');
            }

            // création écriture
            $ecriture = new Ecriture();
            $ecriture->setOperation($operation);
            $ecriture->setDebit($montant);
            $ecriture->setCredit('0.00');

            $ecriture->setCompte($compteCharge);

            $exercice = $exerciceRepository->findOneBy(
                ['statut' => 'ouvert'],
            );

            if (!$exercice) {
                // Il est prudent de gérer le cas où aucun exercice n'est ouvert
                throw new \Exception("Aucun exercice ouvert n'a été trouvé !");
            }

            $ecriture->setExercice($exercice);

            $operation->addEcriture($ecriture);

            $coproprieteSelectionnee = $form->get('copropriete')->getData();

            // récupérer les lots
            $lots = $lotRepository->findBy(
                [
                    'copropriete' => $coproprieteSelectionnee,
                ]
            );

            // générer les répartitions
            $repartitionService->generer($ecriture, $lots);

            // sécurité finale
            if (!$ecriture->isComptableValid()) {
                throw new \LogicException('Écriture invalide');
            }

            $em->persist($operation);
            $em->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render(
            'operation/new_charge.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    #[Route('/operation/{id}/annuler', name: 'app_operation_annuler', methods: ['POST'])]
    public function annuler(Request $request, Operation $operation, EntityManagerInterface $em): Response
    {
        // 1. Vérification du token CSRF (sécurité)
        if ($this->isCsrfTokenValid('annuler' . $operation->getId(), $request->request->get('_token'))) {

            // 2. Vérification : est-elle déjà annulée ?
            if ($operation->getStatut() === OperationStatut::ANNULE) {
                $this->addFlash('warning', 'Cette opération est déjà annulée.');
                return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_operation_index'));
            }

            // 3. Changement du statut
            $operation->setStatut(OperationStatut::ANNULE);

            // 4. On nettoie les liens (affectations, paiements, factures)
            $this->nettoyerRepercussions($operation, $em);

            // 5. On sauvegarde tout en base de données
            $em->flush();

            $this->addFlash('success', 'L’opération a bien été annulée et les soldes ont été mis à jour.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');
        }

        // On redirige vers la page d'où vient l'utilisateur
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_operation_index'));
    }

    private function nettoyerRepercussions(
        Operation $operation,
        EntityManagerInterface $em,
    ): void {
        // Selon ton Enum OperationType
        switch ($operation->getType()->value) {

            case 'paiement':
                $paiement = $operation->getPaiement();

                if ($paiement) {

                    foreach ($paiement->getAffectations() as $affectation) {

                        $ligneAppel = $affectation->getLigneAppel();
                        if ($ligneAppel) {
                            $nouveauMontantRegle = $ligneAppel->getMontantRegle() - $affectation->getMontant();
                            $ligneAppel->setMontantRegle(max(0, $nouveauMontantRegle));
                            $ligneAppel->setSoldee(false);
                        }
                        $em->remove($affectation);
                    }
                }
                break;

            case 'charge': //Facture fournisseur
                // 1. On va chercher la facture fournisseur liée à cette opération
                $facture = $em->getRepository(FactureFournisseur::class)->findOneBy(['operation' => $operation]);

                if ($facture) {
                    // Sécurité : Si la facture est marquée comme payée, on refuse l'annulation
                    if ($facture->isSoldee()) { // Ou une vérification sur ton statut de paiement
                        throw new \LogicException("Impossible d'annuler cette charge car elle est associée à un paiement fournisseur actif. Annulez d'abord le paiement.");
                    }

                    // 2. On libère la facture (elle redevient modifiable ou passe à un statut "annulé")
                    // Selon ce que tu as prévu, par exemple :
                    $facture->setComptabilisee(false);
                }
                break;

            case 'appel_fonds': // ⚠️ Vérifie la correspondance avec ton OperationType Enum
                // 🔍 Grâce à ta nouvelle relation, on trouve l'appel de fonds direct branché sur cette opération
                $appelFond = $em->getRepository(\App\Entity\AppelFond::class)->findOneBy([
                    'operation' => $operation
                ]);

                if ($appelFond) {
                    // 🛡️ GARDE-FOU : On vérifie si un copropriétaire a déjà payé tout ou partie
                    foreach ($appelFond->getLigneAppelFonds() as $ligne) {
                        if ($ligne->getMontantRegle() !== null && (float) $ligne->getMontantRegle() > 0) {
                            throw new \LogicException("Impossible d'annuler cet appel de fonds global : des règlements y sont déjà associés.");
                        }
                    }

                    // 💣 PURGE EN CASCADE : On supprime les lignes de dettes des copropriétaires
                    foreach ($appelFond->getLigneAppelFonds() as $ligne) {
                        $em->remove($ligne);
                    }
                    
                    $budget = $appelFond->getBudget();
                    if ($budget) {
                        $budget->setVerrouille(false);
                    }

                    // 🗑️ On supprime l'en-tête de l'appel de fonds
                    $em->remove($appelFond);
                }
                break;
        }
    }
}
