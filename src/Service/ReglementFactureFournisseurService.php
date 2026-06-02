<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\FactureFournisseur;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReglementFactureFournisseurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
    ) {}

    public function regler(
        FactureFournisseur $facture
    ): void {

        if ($facture->isSoldee()) {
            throw new \LogicException(
                'Facture déjà soldée'
            );
        }

        // =====================
        // Comptes
        // =====================

        $compteFournisseur =
            $facture
            ->getFournisseur()
            ->getCompte();

        $compteBanque =
            $this->compteRepository
            ->findOneBy([
                'numero' => '512000'
            ]);

        if (!$compteBanque) {
            throw new \LogicException(
                'Compte banque introuvable'
            );
        }

        // =====================
        // Operation
        // =====================

        $operation = new Operation();

        $operation->setDate(
            new \DateTimeImmutable()
        );

        $operation->setLibelle(
            'Règlement facture ' .
                $facture->getNumero()
        );

        $operation->setPiece(
            $facture->getNumero()
        );

        $operation->setType(
            OperationType::PAIEMENT_FOURNISSEUR
        );

        // =====================
        // Débit fournisseur
        // =====================

        $debit = new Ecriture();

        $debit->setCompte(
            $compteFournisseur
        );

        $debit->setDebit(
            $facture->getMontant()
        );

        $debit->setCredit('0.00');

        $debit->setDate(
            new \DateTimeImmutable()
        );

        $debit->setExercice(
            $facture->getExercice()
        );

        $debit->setOperation(
            $operation
        );

        // =====================
        // Crédit banque
        // =====================

        $credit = new Ecriture();

        $credit->setCompte(
            $compteBanque
        );

        $credit->setDebit('0.00');

        $credit->setCredit(
            $facture->getMontant()
        );

        $credit->setDate(
            new \DateTimeImmutable()
        );

        $credit->setExercice(
            $facture->getExercice()
        );

        $credit->setOperation(
            $operation
        );

        // =====================
        // Liaison
        // =====================

        $operation->addEcriture($debit);

        $operation->addEcriture($credit);

        // =====================
        // Facture soldée
        // =====================

        $facture->setMontantRegle(
            $facture->getMontant()
        );

        $facture->setSoldee(true);

        // =====================
        // Persist
        // =====================

        $this->entityManager->persist(
            $operation
        );

        $this->entityManager->flush();
    }
}
