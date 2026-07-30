<?php

namespace App\Service;

use App\Entity\Compte;
use App\Entity\Coproprietaire;
use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\JournalCode;
use App\Enum\OperationStatut;
use App\Enum\OperationType;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;

class ComptabiliteService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JournalRepository $journalRepository,
    ) {}

    /**
     * Crée et prépare une opération comptable.
     *
     * Le journal est automatiquement déterminé selon le type d'opération.
     */
    public function creerOperation(
        \DateTimeImmutable $date,
        string $libelle,
        OperationType $type,
        ?string $piece = null,
    ): Operation {
        $journalCode = $this->determinerJournal($type);

        $journal = $this->journalRepository->findByCode(
            $journalCode
        );

        $operation = new Operation();

        $operation
            ->setDate($date)
            ->setLibelle($libelle)
            ->setType($type)
            ->setJournal($journal)
            ->setPiece($piece)
            ->setStatut(OperationStatut::VALIDE);

        $this->em->persist($operation);

        return $operation;
    }

    /**
     * Crée une écriture au débit.
     */
    public function creerDebit(
        Operation $operation,
        Exercice $exercice,
        Compte $compte,
        float|string $montant,
        ?Coproprietaire $coproprietaire = null,
    ): Ecriture {
        $montant = $this->normaliserMontant($montant);

        if ($montant <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’une écriture au débit doit être supérieur à zéro.'
            );
        }

        $ecriture = new Ecriture();

        $ecriture
            ->setOperation($operation)
            ->setExercice($exercice)
            ->setCompte($compte)
            ->setCoproprietaire($coproprietaire)
            ->setDate($operation->getDate())
            ->setDebit(
                number_format(
                    $montant,
                    2,
                    '.',
                    ''
                )
            )
            ->setCredit('0.00');

        $operation->addEcriture($ecriture);

        $this->em->persist($ecriture);

        return $ecriture;
    }

    /**
     * Crée une écriture au crédit.
     */
    public function creerCredit(
        Operation $operation,
        Exercice $exercice,
        Compte $compte,
        float|string $montant,
        ?Coproprietaire $coproprietaire = null,
    ): Ecriture {
        $montant = $this->normaliserMontant($montant);

        if ($montant <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’une écriture au crédit doit être supérieur à zéro.'
            );
        }

        $ecriture = new Ecriture();

        $ecriture
            ->setOperation($operation)
            ->setExercice($exercice)
            ->setCompte($compte)
            ->setCoproprietaire($coproprietaire)
            ->setDate($operation->getDate())
            ->setDebit('0.00')
            ->setCredit(
                number_format(
                    $montant,
                    2,
                    '.',
                    ''
                )
            );

        $operation->addEcriture($ecriture);

        $this->em->persist($ecriture);

        return $ecriture;
    }

    /**
     * Vérifie que le total des débits est égal au total des crédits.
     */
    public function verifierEquilibre(Operation $operation): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($operation->getEcritures() as $ecriture) {
            $totalDebit += (float) $ecriture->getDebit();
            $totalCredit += (float) $ecriture->getCredit();
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if ($totalDebit !== $totalCredit) {
            throw new \RuntimeException(
                sprintf(
                    'L’opération comptable n’est pas équilibrée : débit %.2f €, crédit %.2f €, écart %.2f €.',
                    $totalDebit,
                    $totalCredit,
                    round($totalDebit - $totalCredit, 2)
                )
            );
        }
    }

    /**
     * Enregistre les opérations et écritures préparées.
     */
    public function enregistrer(): void
    {
        $this->em->flush();
    }

    /**
     * Détermine le journal associé au type d'opération.
     */
    private function determinerJournal(
        OperationType $type,
    ): JournalCode {
        return match ($type) {
            OperationType::A_NOUVEAU
            => JournalCode::AN,

            OperationType::CHARGE
            => JournalCode::FG,

            OperationType::PAIEMENT,
            OperationType::PAIEMENT_FOURNISSEUR
            => JournalCode::BQ,

            OperationType::APPEL_FONDS
            => JournalCode::AC,

            OperationType::REGULARISATION,
            OperationType::CLOTURE,
            OperationType::APPROBATION_COMPTES,
            OperationType::TRANSFERT_DETTE
            => JournalCode::OD,
        };
    }

    /**
     * Convertit et arrondit un montant à deux décimales.
     */
    private function normaliserMontant(
        float|string $montant,
    ): float {
        if (is_string($montant)) {
            $montant = str_replace(
                [' ', ','],
                ['', '.'],
                $montant
            );
        }

        if (!is_numeric($montant)) {
            throw new \InvalidArgumentException(
                'Le montant fourni n’est pas numérique.'
            );
        }

        return round((float) $montant, 2);
    }
}
