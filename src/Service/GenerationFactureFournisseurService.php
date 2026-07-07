<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use Doctrine\ORM\EntityManagerInterface;

class GenerationFactureFournisseurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GenerationRepartitionService $generationRepartitionService,
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function generer(
        FactureFournisseur $facture
    ): void {
        $compteCharge = $facture
            ->getTypeCharge()
            ->getCompte();

        if (!$compteCharge) {
            throw new \LogicException(
                'Compte de charge introuvable.'
            );
        }

        $compteFournisseur = $facture
            ->getFournisseur()
            ->getCompte();

        if (!$compteFournisseur) {
            throw new \LogicException(
                'Compte fournisseur introuvable.'
            );
        }

        $operation = $this->comptabiliteService->creerOperation(
            $facture->getDateFacture(),
            $facture->getLibelle(),
            OperationType::CHARGE,
            $facture->getNumero()
        );

        $operation
            ->setTypeCharge($facture->getTypeCharge())
            ->setFournisseur($facture->getFournisseur());

        $debit = $this->comptabiliteService->creerDebit(
            $operation,
            $facture->getExercice(),
            $compteCharge,
            $facture->getMontant()
        );

        $this->comptabiliteService->creerCredit(
            $operation,
            $facture->getExercice(),
            $compteFournisseur,
            $facture->getMontant()
        );

        $facture->setOperation($operation);
        $facture->setComptabilisee(true);

        $this->generationRepartitionService->generer(
            $debit,
            $facture
        );

        $this->comptabiliteService->enregistrer($operation);

        $this->entityManager->flush();
    }
}
