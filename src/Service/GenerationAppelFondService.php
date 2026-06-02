<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Ecriture;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerationAppelFondService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
    ) {}

    public function generer(
        AppelFond $appelFond
    ): void {

        // =====================
        // Compte produit
        // =====================

        $compteProduit =
            $this->compteRepository
            ->findOneBy(
                [
                    'numero' => '701000'
                ]
            );

        if (!$compteProduit) {
            throw new \LogicException(
                'Compte 701000 introuvable'
            );
        }

        // =====================
        // Operation comptable
        // =====================

        $operation = new Operation();

        $operation->setDate(
            $appelFond->getDateAppel()
        );

        $operation->setLibelle(
            $appelFond->getLibelle()
                ?? 'Appel de fonds'
        );

        $operation->setPiece(
            sprintf(
                'AF-%s-%02d',
                $appelFond
                    ->getDateAppel()
                    ->format('Y'),
                $appelFond->getNumero()
            )
        );

        $operation->setType(
            OperationType::APPEL_FONDS
        );

        // =====================
        // Génération écritures
        // =====================

        foreach (
            $appelFond->getLigneAppelFonds()
            as $ligne
        ) {

            $coproprietaire =
                $ligne->getCoproprietaire();

            if (!$coproprietaire) {
                continue;
            }

            $compteCopro =
                $coproprietaire->getCompte();

            if (!$compteCopro) {

                throw new \LogicException(
                    sprintf(
                        'Le copropriétaire %s n\'a pas de compte',
                        $coproprietaire
                    )
                );
            }

            // =====================
            // Débit lot
            // =====================

            $debit = new Ecriture();

            $debit->setDate(
                $appelFond->getDateAppel()
            );

            $debit->setOperation(
                $operation
            );

            $debit->setExercice(
                $appelFond
                    ->getBudget()
                    ->getExercice()
            );

            $debit->setCompte(
                $compteCopro
            );

            $debit->setDebit(
                $ligne->getMontant()
            );

            $debit->setCredit('0.00');

            $debit->setCoproprietaire(
                $coproprietaire
            );

            $operation->addEcriture(
                $debit
            );

            // =====================
            // Crédit produit
            // =====================

            $credit = new Ecriture();

            $credit->setDate(
                $appelFond->getDateAppel()
            );

            $credit->setOperation(
                $operation
            );

            $credit->setExercice(
                $appelFond
                    ->getBudget()
                    ->getExercice()
            );

            $credit->setCompte(
                $compteProduit
            );

            $credit->setDebit('0.00');

            $credit->setCredit(
                $ligne->getMontant()
            );

            $operation->addEcriture(
                $credit
            );
        }

        // =====================
        // Validation
        // =====================

        if (!$operation->isEquilibree()) {

            throw new \LogicException(
                'Operation non équilibrée'
            );
        }

        $this->entityManager
            ->persist($operation);

        $this->entityManager
            ->flush();
    }
}
