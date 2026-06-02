<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\FactureFournisseur;
use App\Entity\Operation;
use App\Enum\OperationType;
use Doctrine\ORM\EntityManagerInterface;

class GenerationFactureFournisseurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function generer(
        FactureFournisseur $facture
    ): void {
        // =====================
        // Compte charge
        // =====================

        $compteCharge = $facture
            ->getTypeCharge()
            ->getCompte();

        // =====================
        // Compte fournisseur
        // =====================

        $compteFournisseur = $facture
            ->getFournisseur()
            ->getCompte();

        // =====================
        // Operation
        // =====================

        $operation = new Operation();

        $operation->setDate(
            $facture->getDateFacture()
        );

        $operation->setLibelle(
            $facture->getLibelle()
        );

        $operation->setPiece(
            $facture->getNumero()
        );

        $operation->setType(
            OperationType::CHARGE
        );

        // =====================
        // Débit charge
        // =====================

        $debit = new Ecriture();

        $debit->setCompte(
            $compteCharge
        );

        $debit->setDebit(
            $facture->getMontant()
        );

        $debit->setCredit('0.00');

        $debit->setDate(
            $facture->getDateFacture()
        );

        $debit->setOperation(
            $operation
        );

        $debit->setExercice($facture->getExercice());

        // =====================
        // Crédit fournisseur
        // =====================

        $credit = new Ecriture();

        $credit->setCompte(
            $compteFournisseur
        );

        $credit->setDebit('0.00');

        $credit->setCredit(
            $facture->getMontant()
        );

        $credit->setDate(
            $facture->getDateFacture()
        );

        $credit->setOperation(
            $operation
        );

        $credit->setExercice($facture->getExercice());

        // =====================
        // Liaison
        // =====================

        $operation->addEcriture($debit);

        $operation->addEcriture($credit);

        $facture->setOperation($operation);
        $facture->setComptabilisee(true);

        // =====================
        // Persist
        // =====================

        $this->entityManager->persist(
            $operation
        );

        $this->entityManager->flush();
    }
}
