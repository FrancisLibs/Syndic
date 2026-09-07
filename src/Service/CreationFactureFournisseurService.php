<?php

namespace App\Service;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use App\Enum\FactureFournisseurType;
use Doctrine\ORM\EntityManagerInterface;

class CreationFactureFournisseurService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GenerationFactureFournisseurService $generationService,
    ) {}

    public function creerEtComptabiliser(
        Exercice $exercice,
        Fournisseur $fournisseur,
        TypeCharge $typeCharge,
        \DateTimeImmutable $dateFacture,
        ?string $numero,
        string $libelle,
        string $montant,
        FactureFournisseurType $type = FactureFournisseurType::FACTURE,
        ?Coproprietaire $coproprietaireAvanceur = null,
        ?int $volumeEau = null,
    ): FactureFournisseur {
        $this->valider(
            $exercice,
            $fournisseur,
            $typeCharge,
            $dateFacture,
            $libelle,
            $montant,
        );

        $facture = new FactureFournisseur();

        $facture
            ->setExercice($exercice)
            ->setFournisseur($fournisseur)
            ->setTypeCharge($typeCharge)
            ->setDateFacture($dateFacture)
            ->setNumero($numero)
            ->setLibelle(trim($libelle))
            ->setMontant($montant)
            ->setType($type)
            ->setCoproprietaireAvanceur(
                $coproprietaireAvanceur
            )
            ->setVolumeEau($volumeEau)
        ;

        $this->entityManager->persist($facture);

        $this->generationService->generer($facture);

        return $facture;
    }

    private function valider(
        Exercice $exercice,
        Fournisseur $fournisseur,
        TypeCharge $typeCharge,
        \DateTimeImmutable $dateFacture,
        string $libelle,
        string $montant
    ): void {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Impossible de créer un document fournisseur dans un exercice clôturé.'
            );
        }

        if (
            $dateFacture < $exercice->getDateDebut()
            || $dateFacture > $exercice->getDateFin()
        ) {
            throw new \LogicException(
                'La date de facture ne correspond pas à la période de l’exercice.'
            );
        }

        if (trim($libelle) === '') {
            throw new \LogicException(
                'Le libellé du document fournisseur est obligatoire.'
            );
        }

        if ((float) $montant <= 0) {
            throw new \LogicException(
                'Le montant du document fournisseur doit être supérieur à zéro.'
            );
        }

        if ($fournisseur->getCompte() === null) {
            throw new \LogicException(
                'Le fournisseur ne possède aucun compte comptable.'
            );
        }

        if ($typeCharge->getCompte() === null) {
            throw new \LogicException(
                'Le type de charge ne possède aucun compte comptable.'
            );
        }
    }
}
