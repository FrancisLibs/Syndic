<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Fournisseur;
use App\Entity\Operation;
use App\Entity\ReglementFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ReglementFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompteRepository $compteRepository,
        private readonly ComptabiliteService $comptabiliteService,
    ) {}

    public function enregistrer(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateReglement,
        string $montant,
        ?string $reference = null,
    ): ReglementFournisseur {

        $montant = $this->normaliserMontant(
            $montant
        );

        $this->valider(
            $fournisseur,
            $exercice,
            $dateReglement,
            $montant
        );

        $operation =
            $this->creerEcritureReglement(
                $fournisseur,
                $exercice,
                $dateReglement,
                $montant,
                $reference
            );

        $reglement =
            new ReglementFournisseur();

        $reglement
            ->setDateReglement(
                $dateReglement
            )
            ->setMontant(
                $montant
            )
            ->setReference(
                $reference
            )
            ->setFournisseur(
                $fournisseur
            )
            ->setExercice(
                $exercice
            )
            ->setOperation(
                $operation
            );

        $this->entityManager->persist(
            $reglement
        );

        $this->comptabiliteService
            ->enregistrer();

        return $reglement;
    }

    private function valider(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateReglement,
        string $montant
    ): void {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’enregistrer un règlement sur un exercice clôturé.'
            );
        }

        if (
            $dateReglement < $exercice->getDateDebut()
            || $dateReglement > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date de règlement ne correspond pas à la période de l’exercice.'
            );
        }

        if ((float) $montant <= 0) {
            throw new \LogicException(
                'Le montant du règlement doit être supérieur à zéro.'
            );
        }

        if ($fournisseur->getCompte() === null) {
            throw new \LogicException(
                'Le fournisseur ne possède aucun compte comptable.'
            );
        }
    }

    private function creerEcritureReglement(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateReglement,
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
            'Règlement '
            . $fournisseur->getNom();

        if ($reference !== '') {
            $libelle .=
                ' - '
                . $reference;
        }

        $operation =
            $this->comptabiliteService
            ->creerOperation(
                $dateReglement,
                $libelle,
                OperationType::PAIEMENT_FOURNISSEUR,
                $reference !== ''
                    ? $reference
                    : null
            );

        $operation->setFournisseur(
            $fournisseur
        );

        $this->comptabiliteService
            ->creerDebit(
                $operation,
                $exercice,
                $compteFournisseur,
                (float) $montant
            );

        $this->comptabiliteService
            ->creerCredit(
                $operation,
                $exercice,
                $compteBanque,
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
                'Montant de règlement invalide.'
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
