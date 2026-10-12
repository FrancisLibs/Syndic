<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Fournisseur;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RemboursementFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompteRepository $compteRepository,
        private readonly ComptabiliteService $comptabiliteService,
    ) {}


    public function enregistrer(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateRemboursement,
        string $montant,
        ?string $reference = null,
    ): Operation {
        $montant = $this->normaliserMontant(
            $montant
        );

        $this->valider(
            $fournisseur,
            $exercice,
            $dateRemboursement,
            $montant
        );

        $operation = $this->creerEcritureRemboursement(
            $fournisseur,
            $exercice,
            $dateRemboursement,
            $montant,
            $reference
        );

        $this->comptabiliteService->enregistrer();

        return $operation;
    }

    private function valider(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateRemboursement,
        string $montant
    ): void {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’enregistrer un remboursement sur un exercice clôturé.'
            );
        }

        if (
            $dateRemboursement < $exercice->getDateDebut()
            || $dateRemboursement > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date de remboursement ne correspond pas à la période de l’exercice.'
            );
        }

        if ((float) $montant <= 0) {
            throw new \LogicException(
                'Le montant du remboursement doit être supérieur à zéro.'
            );
        }

        if ($fournisseur->getCompte() === null) {
            throw new \LogicException(
                'Le fournisseur ne possède aucun compte comptable.'
            );
        }
    }

    private function creerEcritureRemboursement(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateRemboursement,
        string $montant,
        ?string $reference
    ): Operation {
        $compteFournisseur =
            $fournisseur->getCompte();

        if ($compteFournisseur === null) {
            throw new \LogicException(
                'Le compte fournisseur est introuvable.'
            );
        }

        $compteBanque =
            $this->compteRepository
            ->findByNumeroOrFail(
                '512000'
            );

        $reference = trim(
            (string) $reference
        );

        $libelle =
            'Remboursement '
            . $fournisseur->getNom();

        if ($reference !== '') {
            $libelle .=
                ' - '
                . $reference;
        }

        $operation =
            $this->comptabiliteService
            ->creerOperation(
                $dateRemboursement,
                $libelle,
                OperationType::REMBOURSEMENT_FOURNISSEUR,
                $reference !== ''
                    ? $reference
                    : null
            );

        $operation->setFournisseur(
            $fournisseur
        );

        // Entrée d'argent en banque
        $this->comptabiliteService
            ->creerDebit(
                $operation,
                $exercice,
                $compteBanque,
                (float) $montant
            );

        // Solde du compte fournisseur
        $this->comptabiliteService
            ->creerCredit(
                $operation,
                $exercice,
                $compteFournisseur,
                (float) $montant
            );

        return $operation;
    }

    private function normaliserMontant(
        float|string $montant
    ): string {
        if (is_string($montant)) {
            $montant = str_replace(
                [' ', ','],
                ['', '.'],
                trim($montant)
            );
        }

        if (!is_numeric($montant)) {
            throw new \LogicException(
                'Montant de remboursement invalide.'
            );
        }

        return number_format(
            round(
                (float) $montant,
                2
            ),
            2,
            '.',
            ''
        );
    }
}
