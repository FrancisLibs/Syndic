<?php

namespace App\Service;

use App\Entity\Operation;
use App\Enum\OperationStatut;
use App\Enum\OperationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Compte;
use App\Entity\Coproprietaire;
use App\Entity\Ecriture;
use App\Entity\Exercice;

class ComptabiliteService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function creerOperation(
        \DateTimeImmutable $date,
        string $libelle,
        OperationType $type,
        ?string $piece = null,
    ): Operation {

        $operation = new Operation();

        $operation
            ->setDate($date)
            ->setLibelle($libelle)
            ->setPiece($piece)
            ->setType($type)
            ->setStatut(OperationStatut::VALIDE);

        return $operation;
    }

    public function creerDebit(
        Operation $operation,
        Exercice $exercice,
        Compte $compte,
        float|string $montant,
        ?Coproprietaire $coproprietaire = null,
    ): Ecriture {
        return $this->creerEcriture(
            $operation,
            $exercice,
            $compte,
            $montant,
            '0.00',
            $coproprietaire
        );
    }

    public function creerCredit(
        Operation $operation,
        Exercice $exercice,
        Compte $compte,
        float|string $montant,
        ?Coproprietaire $coproprietaire = null,
    ): Ecriture {
        return $this->creerEcriture(
            $operation,
            $exercice,
            $compte,
            '0.00',
            $montant,
            $coproprietaire
        );
    }

    private function creerEcriture(
        Operation $operation,
        Exercice $exercice,
        Compte $compte,
        float|string $debit,
        float|string $credit,
        ?Coproprietaire $coproprietaire = null,
    ): Ecriture {
        $ecriture = new Ecriture();

        $ecriture
            ->setDate($operation->getDate())
            ->setOperation($operation)
            ->setExercice($exercice)
            ->setCompte($compte)
            ->setDebit($debit)
            ->setCredit($credit)
            ->setCoproprietaire($coproprietaire);

        $operation->addEcriture($ecriture);

        return $ecriture;
    }

    public function enregistrer(
        Operation $operation
    ): void {

        if (!$operation->isEquilibree()) {
            throw new \LogicException(
                'L’opération comptable n’est pas équilibrée.'
            );
        }

        $this->em->persist($operation);
    }
}
