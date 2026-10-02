<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use App\Enum\OperationType;
use App\Repository\CompteRepository;

class GenerationFnpService
{
    public function __construct(
        private ComptabiliteService $comptabiliteService,
        private GenerationRepartitionService $generationRepartitionService,
        private CompteRepository $compteRepository,
    ) {}

    public function generer(
        Exercice $exercice,
        TypeCharge $typeCharge,
        \DateTimeImmutable $date,
        string $libelle,
        float $montant,
        ?Fournisseur $fournisseur = null,
        ?string $piece = null,
    ): void {
        if ($montant <= 0) {
            throw new \LogicException(
                'Le montant de la FNP doit être positif.'
            );
        }

        $compteCharge = $typeCharge->getCompte();

        if ($compteCharge === null) {
            throw new \LogicException(
                'Le type de charge ne possède aucun compte comptable.'
            );
        }

        $compteFnp = $this
            ->compteRepository
            ->findByNumeroOrFail('408000');

        /*
         * Débit : compte de charge
         * Crédit : 408000 Factures non parvenues
         */
        $operation = $this
            ->comptabiliteService
            ->creerOperation(
                $date,
                $libelle,
                OperationType::FNP,
                $piece
            );

        $operation
            ->setTypeCharge($typeCharge)
            ->setFournisseur($fournisseur);

        $ecritureCharge = $this
            ->comptabiliteService
            ->creerDebit(
                $operation,
                $exercice,
                $compteCharge,
                $montant
            );

        $this
            ->comptabiliteService
            ->creerCredit(
                $operation,
                $exercice,
                $compteFnp,
                $montant
            );

        /*
         * La charge doit être répartie entre les copropriétaires,
         * comme une facture fournisseur normale.
         */
        if (!$typeCharge->isEau()) {
            $this
                ->generationRepartitionService
                ->generer(
                    $ecritureCharge,
                    $exercice,
                    $date,
                    $montant
                );
        }

        /**
         * Un seul flush à la fin :
         * opération + écritures + répartitions.
         */
        $this
            ->comptabiliteService
            ->enregistrer();
    }
}
