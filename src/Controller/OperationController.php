<?php

namespace App\Controller;

use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Form\OperationFormType;
use App\Repository\ExerciceRepository;
use App\Repository\LotRepository;
use App\Service\RepartitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OperationController extends AbstractController
{
    #[Route('/operation', name: 'app_operation')]
    public function index(): Response
    {
        return $this->render('operation/index.html.twig', [
            'controller_name' => 'OperationController',
        ]);
    }

    #[Route('/operation/{id}', name: 'operation_show')]
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
            dd($compteCharge);

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
}
