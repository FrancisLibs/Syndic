<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Entity\Paiement;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerationPaiementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
    ) {}

    public function generer(
        Paiement $paiement
    ): void {

        // =====================
        // Comptes
        // =====================

        $compteBanque = $this->compteRepository
            ->findOneBy(
                [
                    'numero' => '512000'
                ]
            );

        if (!$compteBanque) {
            throw new \LogicException(
                'Compte banque introuvable'
            );
        }

        $compteCoproprietaire = $paiement->getCoproprietaire()->getCompte();

        // =====================
        // Operation
        // =====================

        $operation = new Operation();

        $operation->setDate($paiement->getDatePaiement());
        $operation->setLibelle('Paiement copropriétaire');
        $operation->setPiece($paiement->getReference());
        $operation->setType(OperationType::PAIEMENT);

        // =====================
        // Débit banque
        // =====================

        $debit = new Ecriture();

        $debit->setCompte($compteBanque);
        $debit->setDebit($paiement->getMontant());
        $debit->setCredit('0.00');
        $debit->setDate($paiement->getDatePaiement());
        $debit->setExercice($paiement->getExercice());
        $debit->setOperation($operation);

        // =====================
        // Crédit lot
        // =====================

        $credit = new Ecriture();

        $credit->setCompte($compteCoproprietaire);
        $credit->setCoproprietaire($paiement->getCoproprietaire());
        $credit->setDebit('0.00');
        $credit->setCredit($paiement->getMontant());
        $credit->setDate($paiement->getDatePaiement());
        $credit->setExercice($paiement->getExercice());
        $credit->setOperation($operation);

        // =====================
        // Liaison
        // =====================

        $operation->addEcriture($debit);

        $operation->addEcriture($credit);

        $paiement->setOperation(
            $operation
        );

        // =====================
        // Persist
        // =====================

        $this->entityManager->persist(
            $operation
        );

        $this->entityManager->flush();
    }
}
