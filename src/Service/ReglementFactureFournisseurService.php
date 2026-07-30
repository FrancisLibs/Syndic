<?php

namespace App\Service;

use App\Entity\FactureFournisseur;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ReglementFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompteRepository $compteRepository,
        private readonly ExerciceRepository $exerciceRepository,
        private readonly ComptabiliteService $comptabiliteService,
    ) {}

    public function regler(
        FactureFournisseur $facture,
        ?\DateTimeImmutable $dateReglement = null
    ): void {
        if ($facture->isSoldee()) {
            throw new \LogicException(
                'Cette facture est déjà soldée.'
            );
        }

        $exerciceActif = $this->exerciceRepository
            ->findActif();

        if ($exerciceActif === null) {
            throw new \LogicException(
                'Aucun exercice actif trouvé.'
            );
        }

        if ($exerciceActif->isCloture()) {
            throw new \LogicException(
                'Impossible d’enregistrer un règlement sur un exercice clôturé.'
            );
        }

        $date = $dateReglement
            ?? new \DateTimeImmutable();

        if (
            $date < $exerciceActif->getDateDebut()
            || $date > $exerciceActif->getDateFin()
        ) {
            throw new \LogicException(
                'La date de règlement ne correspond pas à la période de l’exercice actif.'
            );
        }

        $dateFacture = $facture->getDateFacture();

        if (
            $dateFacture !== null
            && $date < $dateFacture
        ) {
            throw new \LogicException(
                'La date de règlement ne peut pas être antérieure à la date de facture.'
            );
        }

        $fournisseur = $facture->getFournisseur();

        if ($fournisseur === null) {
            throw new \LogicException(
                'Aucun fournisseur n’est défini pour cette facture.'
            );
        }

        $coproprietaireAvanceur =
            $facture->getCoproprietaireAvanceur();

        if ($coproprietaireAvanceur !== null) {
            $compteTiers = $coproprietaireAvanceur
                ->getCompte();

            if ($compteTiers === null) {
                throw new \LogicException(
                    'Le compte du copropriétaire avanceur est introuvable.'
                );
            }

            $libelleTiers =
                (string) $coproprietaireAvanceur;
        } else {
            $compteTiers = $fournisseur
                ->getCompte();

            if ($compteTiers === null) {
                throw new \LogicException(
                    'Le compte fournisseur est introuvable.'
                );
            }

            $libelleTiers = $fournisseur->getNom();
        }

        $compteBanque = $this->compteRepository
            ->findByNumeroOrFail('512000');

        $montantReglement =
            (float) $facture->getResteAPayer();

        if ($montantReglement <= 0) {
            throw new \LogicException(
                'Aucun montant ne reste à régler.'
            );
        }

        $numeroFacture = trim(
            (string) $facture->getNumero()
        );

        $piece = $numeroFacture !== ''
            ? $numeroFacture
            : null;

        $libelleOperation = 'Règlement '
            . $libelleTiers;

        if ($numeroFacture !== '') {
            $libelleOperation .=
                ' - pièce ' . $numeroFacture;
        }

        $operation = $this->comptabiliteService
            ->creerOperation(
                $date,
                $libelleOperation,
                OperationType::PAIEMENT_FOURNISSEUR,
                $piece
            );

        $operation->setFournisseur(
            $fournisseur
        );

        $this->comptabiliteService
            ->creerDebit(
                $operation,
                $exerciceActif,
                $compteTiers,
                $montantReglement,
                $coproprietaireAvanceur
            );

        $this->comptabiliteService
            ->creerCredit(
                $operation,
                $exerciceActif,
                $compteBanque,
                $montantReglement
            );

        $nouveauMontantRegle =
            (float) $facture->getMontantRegle()
            + $montantReglement;

        $facture
            ->setMontantRegle(
                number_format(
                    $nouveauMontantRegle,
                    2,
                    '.',
                    ''
                )
            )
            ->setDateReglement($date)
            ->setSoldee(true);

        $this->comptabiliteService
            ->enregistrer();
    }
}
