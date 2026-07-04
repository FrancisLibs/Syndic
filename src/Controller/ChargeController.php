<?php

namespace App\Controller;

use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Form\ChargeType;
use App\Repository\CompteRepository;
use App\Repository\ExerciceRepository;
use App\Repository\LotRepository;
use App\Repository\OperationRepository;
use App\Service\RepartitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChargeController extends AbstractController
{
    #[Route('/charge', name: 'app_charge_index')]
    public function index(
        OperationRepository $operationRepo
    ): Response {

        $charges = $operationRepo->findBy(
            [
                'type' => OperationType::CHARGE
            ],
            [
                'date' => 'DESC'
            ]
        );

        $mouvements = [];

        $totalCharges = 0;

        foreach ($charges as $operation) {

            foreach ($operation->getEcritures() as $ecriture) {

                // On ne garde que les comptes de charge
                if (str_starts_with($ecriture->getCompte()->getNumero(),'6')
                ) {

                    $montant = (float) $ecriture->getDebit();

                    $totalCharges += $montant;

                    $mouvements[] = [
                        'date' => $operation->getDate(),
                        'type' => 'Charge',
                        'libelle' => $operation->getLibelle(),
                        'debit' => $montant,
                        'credit' => 0,
                    ];
                }
            }
        }

        return $this->render(
            'charge/index.html.twig',
            [
                'mvt' => $mouvements,
                'totalCharges' => $totalCharges,
            ]
        );
    }

    #[Route('/charge/nouvelle', name: 'app_charge_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CompteRepository $compteRepo,
        ExerciceRepository $exerciceRepo,
        RepartitionService $repartitionService,
        LotRepository $lotRepo
    ): Response {
        $operation = new Operation();
        $operation->setType(OperationType::CHARGE);

        $exercice = $exerciceRepo->findOneBy(['statut' => 'ouvert']);
        if (!$exercice) {
            throw new \LogicException('Aucun exercice ouvert');
        }

        $form = $this->createForm(ChargeType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $typeCharge = $operation->getTypeCharge();
            $typeCompte = $operation->getType();
            $compteCharge = $typeCharge->getCompte();
            $date = $operation->getDate();

             // 🔹 Vérification du compte charge (606)

            $montant = (float) $form->get('montant')->getData();
            if ($montant <= 0) {
                throw new \LogicException('Montant invalide');
            }

            $compteFournisseur = $operation->getFournisseur()->getCompte();
            if (!$compteFournisseur) {
                throw new \LogicException('Compte fournisseur introuvable');
            }

            // ✅ Écriture charge (606)
            $ecritureCharge = new Ecriture();
            $ecritureCharge
                ->setOperation($operation)
                ->setExercice($exercice)
                ->setCompte($compteCharge)
                ->setDebit(number_format($montant, 2, '.', ''))
                ->setCredit('0.00')
                ->setDate($date);

            // ✅ Écriture fournisseur (401)
            $ecritureFournisseur = new Ecriture();
            $ecritureFournisseur
                ->setOperation($operation)
                ->setExercice($exercice)
                ->setCompte($compteFournisseur)
                ->setDebit('0.00')
                ->setCredit(number_format($montant, 2, '.', ''))
                ->setDate($date);
            // Liaison
            $operation->addEcriture($ecritureCharge);
            $operation->addEcriture($ecritureFournisseur);

            // ✅ Répartition des charges
            if ($typeCompte === OperationType::CHARGE) {

                $lots = $lotRepo->findBy(
                    [
                        'copropriete' => $exercice->getCopropriete()
                    ]
                );

                if (empty($lots)) {
                    throw new \LogicException(
                        'Aucun lot dans la copropriété'
                    );
                }

                $repartitionService->generer(
                    $ecritureCharge,
                    $lots,
                    $typeCharge->getModeRepartition()
                );
            }

            $em->persist($operation);
            $em->flush();

            return $this->redirectToRoute('app_charge_index');
        }

        return $this->render(
            'charge/new.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
