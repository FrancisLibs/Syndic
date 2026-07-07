<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReglementFactureFournisseurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function regler(
        FactureFournisseur $facture
    ): void {
        if ($facture->isSoldee()) {
            throw new \LogicException(
                'Facture déjà soldée'
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

        $compteBanque = $this->compteRepository
            ->findByNumeroOrFail('512000');

        $date = new \DateTimeImmutable();

        $operation = $this->comptabiliteService->creerOperation(
            $date,
            'Règlement facture ' . $facture->getNumero(),
            OperationType::PAIEMENT_FOURNISSEUR,
            $facture->getNumero()
        );

        $operation->setFournisseur(
            $facture->getFournisseur()
        );

        $this->comptabiliteService->creerDebit(
            $operation,
            $facture->getExercice(),
            $compteFournisseur,
            $facture->getMontant()
        );

        $this->comptabiliteService->creerCredit(
            $operation,
            $facture->getExercice(),
            $compteBanque,
            $facture->getMontant()
        );

        $facture->setMontantRegle(
            $facture->getMontant()
        );

        $facture->setSoldee(true);

        $this->comptabiliteService->enregistrer($operation);

        $this->entityManager->flush();
    }
}
