<?php

namespace App\Service\Imports;

use App\Entity\FactureFournisseur;
use App\Entity\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use App\Service\CreationFactureFournisseurService;
use Doctrine\ORM\EntityManagerInterface;

final class TraitementImportFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreationFactureFournisseurService $creationFactureService,
    ) {}

    public function traiter(
        ImportFactureFournisseur $ligne
    ): FactureFournisseur {
        if ($ligne->estTraitee()) {
            throw new \LogicException(
                'Cette ligne d’import a déjà été traitée.'
            );
        }

        if ($ligne->estEnTraitement()) {
            throw new \LogicException(
                'Cette ligne d’import est déjà en cours de traitement.'
            );
        }

        try {
            $this->valider($ligne);

            $ligne
                ->setStatut(ImportStatut::TRAITEMENT)
                ->setErreur(null);

            $this->entityManager->flush();

            $facture = $this->creationFactureService
                ->creerEtComptabiliser(
                    exercice: $ligne->getExercice(),
                    fournisseur: $ligne->getFournisseur(),
                    typeCharge: $ligne->getTypeCharge(),
                    dateFacture: $ligne->getDateFacture(),
                    numero: $ligne->getNumero(),
                    libelle: $ligne->getLibelle(),
                    montant: $ligne->getMontant(),
                    coproprietaireAvanceur: $ligne->getCoproprietaireAvanceur(),
                );

            $ligne
                ->setFactureCreee($facture)
                ->setStatut(ImportStatut::TRAITEE)
                ->setErreur(null);

            $this->entityManager->flush();

            return $facture;
        } catch (\Throwable $exception) {
            $ligne
                ->setStatut(ImportStatut::ERREUR)
                ->setErreur($exception->getMessage());

            $this->entityManager->flush();

            throw $exception;
        }
    }

    private function valider(
        ImportFactureFournisseur $ligne
    ): void {
        $exercice = $ligne->getExercice();

        if ($exercice === null) {
            throw new \LogicException(
                'L’exercice est obligatoire.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible d’importer une facture dans un exercice clôturé.'
            );
        }

        if ($ligne->getFournisseur() === null) {
            throw new \LogicException(
                'Le fournisseur est obligatoire.'
            );
        }

        if ($ligne->getTypeCharge() === null) {
            throw new \LogicException(
                'Le type de charge est obligatoire.'
            );
        }

        if ($ligne->getDateFacture() === null) {
            throw new \LogicException(
                'La date de facture est obligatoire.'
            );
        }

        if (
            $ligne->getDateFacture() < $exercice->getDateDebut()
            || $ligne->getDateFacture() > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date de facture ne correspond pas à la période de l’exercice.'
            );
        }

        if (trim((string) $ligne->getLibelle()) === '') {
            throw new \LogicException(
                'Le libellé est obligatoire.'
            );
        }

        if ((float) $ligne->getMontant() <= 0) {
            throw new \LogicException(
                'Le montant de la facture doit être supérieur à zéro.'
            );
        }

        if (
            $ligne->isReglee()
            && $ligne->getDateReglement() === null
        ) {
            throw new \LogicException(
                'La date de règlement est obligatoire pour une facture réglée.'
            );
        }

        if (
            !$ligne->isReglee()
            && $ligne->getDateReglement() !== null
        ) {
            throw new \LogicException(
                'Une facture non réglée ne doit pas avoir de date de règlement.'
            );
        }

        if (
            $ligne->getDateReglement() !== null
            && $ligne->getDateReglement() < $ligne->getDateFacture()
        ) {
            throw new \LogicException(
                'La date de règlement ne peut pas être antérieure à la date de facture.'
            );
        }
    }
}
