<?php

namespace App\Service;

use App\Entity\AffectationReglementFournisseur;
use App\Entity\FactureFournisseur;
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

        $this->comptabiliteService
            ->verifierEquilibre($operation);

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

        $this->comptabiliteService->enregistrer();

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

    public function affecterFacture(
        ReglementFournisseur $reglement,
        FactureFournisseur $facture,
        string $montant
    ): AffectationReglementFournisseur {

        $montant = $this->normaliserMontant($montant);

        // Vérification du fournisseur
        if ($reglement->getFournisseur() !== $facture->getFournisseur()) {
            throw new \LogicException(
                'Le règlement et la facture doivent concerner le même fournisseur.'
            );
        }

        if ($facture->getExercice() !== $reglement->getExercice()) {
            throw new \LogicException(
                'La facture doit appartenir au même exercice que le règlement.'
            );
        }

        // Les avances des copropriétaires sont exclues
        if ($facture->getCoproprietaireAvanceur() !== null) {
            throw new \LogicException(
                'Une facture avancée par un copropriétaire ne peut pas être réglée directement au fournisseur.'
            );
        }

        // Seules les factures ordinaires sont concernées
        if (!$facture->estFacture() || $facture->isFnp()) {
            throw new \LogicException(
                'Ce document ne peut pas recevoir ce type de règlement.'
            );
        }

        if (!$facture->isComptabilisee()) {
            throw new \LogicException(
                'La facture doit être comptabilisée avant son règlement.'
            );
        }

        $montantCentimes = (int) round((float) $montant * 100);
        $disponibleCentimes = (int) round(
            $reglement->getMontantNonAffecte() * 100
        );
        $resteCentimes = (int) round(
            $facture->getResteARegler() * 100
        );

        if ($montantCentimes <= 0) {
            throw new \LogicException(
                'Le montant affecté doit être supérieur à zéro.'
            );
        }

        if ($montantCentimes > $disponibleCentimes) {
            throw new \LogicException(
                'Le montant affecté dépasse le solde disponible du règlement.'
            );
        }

        if ($montantCentimes > $resteCentimes) {
            throw new \LogicException(
                'Le montant affecté dépasse le reste à régler de la facture.'
            );
        }

        $affectation = new AffectationReglementFournisseur();

        $affectation
            ->setFacture($facture)
            ->setReglement($reglement)
            ->setMontant($montant);

        $reglement->addAffectation($affectation);
        $facture->addAffectationReglement($affectation);

        $this->entityManager->persist($affectation);

        return $affectation;
    }

    /**
     * @param array<int, array{
     *     facture: FactureFournisseur,
     *     montant: string
     * }> $affectations
     */
    public function enregistrerAvecAffectations(
        Fournisseur $fournisseur,
        Exercice $exercice,
        \DateTimeImmutable $dateReglement,
        string $montant,
        ?string $reference,
        array $affectations
    ): ReglementFournisseur {

        return $this->entityManager->wrapInTransaction(
            function () use (
                $fournisseur,
                $exercice,
                $dateReglement,
                $montant,
                $reference,
                $affectations
            ): ReglementFournisseur {

                // Création du règlement et des écritures comptables
                $reglement = $this->enregistrer(
                    $fournisseur,
                    $exercice,
                    $dateReglement,
                    $montant,
                    $reference
                );

                // Création des affectations
                foreach ($affectations as $ligne) {

                    if (
                        !isset($ligne['facture'], $ligne['montant'])
                        || !$ligne['facture'] instanceof FactureFournisseur
                    ) {
                        throw new \LogicException(
                            'Une affectation est invalide.'
                        );
                    }

                    $this->affecterFacture(
                        $reglement,
                        $ligne['facture'],
                        $ligne['montant']
                    );
                }

                // Vérification finale
                if ($reglement->getMontantNonAffecte() < 0) {
                    throw new \LogicException(
                        'Le montant des affectations dépasse celui du règlement.'
                    );
                }

                return $reglement;
            }
        );
    }
}
