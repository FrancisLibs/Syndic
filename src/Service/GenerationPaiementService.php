<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerationPaiementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
        private ComptabiliteService $comptabiliteService,
    ) {}

    public function generer(Paiement $paiement): void
    {
        $compteBanque = $this->compteRepository->findOneBy([
            'numero' => '512000'
        ]);

        if (!$compteBanque) {
            throw new \LogicException(
                'Compte banque introuvable'
            );
        }

        $coproprietaire = $paiement->getCoproprietaire();
        $compteCoproprietaire = $coproprietaire->getCompte();

        $operation = $this->comptabiliteService->creerOperation(
            $paiement->getDatePaiement(),
            'Paiement copropriétaire',
            OperationType::PAIEMENT,
            $paiement->getReference()
        );

        $this->comptabiliteService->creerDebit(
            $operation,
            $paiement->getExercice(),
            $compteBanque,
            $paiement->getMontant()
        );

        $this->comptabiliteService->creerCredit(
            $operation,
            $paiement->getExercice(),
            $compteCoproprietaire,
            $paiement->getMontant(),
            $coproprietaire
        );

        $paiement->setOperation($operation);

        $this->comptabiliteService->enregistrer($operation);

        $this->entityManager->flush();
    }
}
