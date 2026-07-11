<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReglementFactureFournisseurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
        private ExerciceRepository $exerciceRepository,
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function regler(
        FactureFournisseur $facture
    ): void {
        if ($facture->isSoldee()) {
            throw new \LogicException(
                'Facture déjà soldée.'
            );
        }

        $exerciceActif = $this->exerciceRepository
            ->findActif();

        if (!$exerciceActif) {
            throw new \LogicException(
                'Aucun exercice actif trouvé.'
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

        $montantReglement = $facture->getResteAPayer();

        if ($montantReglement <= 0) {
            throw new \LogicException(
                'Aucun montant ne reste à régler.'
            );
        }

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
            $exerciceActif,
            $compteFournisseur,
            $montantReglement
        );

        $this->comptabiliteService->creerCredit(
            $operation,
            $exerciceActif,
            $compteBanque,
            $montantReglement
        );

        $facture->setMontantRegle(
            $facture->getMontantRegle()
            + $montantReglement
        );

        $facture->setSoldee(true);

        $this->comptabiliteService->enregistrer(
            $operation
        );

        $this->entityManager->flush();
    }
}