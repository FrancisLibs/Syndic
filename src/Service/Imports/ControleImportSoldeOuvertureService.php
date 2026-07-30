<?php

namespace App\Service\Imports;

use App\Entity\Exercice;
use App\Entity\Imports\ImportSoldeOuverture;
use App\Enum\ImportEtat;
use App\Repository\Imports\ImportSoldeOuvertureRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ControleImportSoldeOuvertureService
{
    public function __construct(
        private readonly ImportSoldeOuvertureRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     lignes: ImportSoldeOuverture[],
     *     totalDebit: float,
     *     totalCredit: float,
     *     ecart: float,
     *     nombreLignes: int,
     *     nombreErreurs: int,
     *     equilibre: bool,
     *     peutImporter: bool
     * }
     */
    public function controler(
        Exercice $exercice,
    ): array {
        $lignes = $this->repository
            ->findPourExercice($exercice);

        $totalDebitCentimes = 0;
        $totalCreditCentimes = 0;
        $nombreErreurs = 0;

        foreach ($lignes as $ligne) {
            $debitCentimes = $this->enCentimes(
                $ligne->getDebit()
            );

            $creditCentimes = $this->enCentimes(
                $ligne->getCredit()
            );

            $totalDebitCentimes += $debitCentimes;
            $totalCreditCentimes += $creditCentimes;

            /*
             * Une ligne déjà importée ne doit plus être
             * recontrôlée ni modifiée.
             */
            if (
                $ligne->getEtat()
                === ImportEtat::IMPORTE
            ) {
                continue;
            }

            $erreurs = $this->controlerLigne(
                $ligne,
                $debitCentimes,
                $creditCentimes
            );

            if ($erreurs !== []) {
                $ligne->marquerErreur(
                    implode(' ', $erreurs)
                );

                ++$nombreErreurs;

                continue;
            }

            /*
             * La ligne avait éventuellement été mise en erreur
             * lors d'un contrôle précédent, puis corrigée
             * directement dans la table d'import.
             */
            if (
                $ligne->getEtat()
                === ImportEtat::ERREUR
            ) {
                $ligne->reinitialiser();
            }
        }

        $this->entityManager->flush();

        $ecartCentimes =
            $totalDebitCentimes
            - $totalCreditCentimes;

        $equilibre = $ecartCentimes === 0;

        return [
            'lignes' => $lignes,

            'totalDebit' =>
            $totalDebitCentimes / 100,

            'totalCredit' =>
            $totalCreditCentimes / 100,

            'ecart' =>
            $ecartCentimes / 100,

            'nombreLignes' =>
            count($lignes),

            'nombreErreurs' =>
            $nombreErreurs,

            'equilibre' =>
            $equilibre,

            'peutImporter' =>
            $lignes !== []
                && $nombreErreurs === 0
                && $equilibre,
        ];
    }

    /**
     * @return string[]
     */
    private function controlerLigne(
        ImportSoldeOuverture $ligne,
        int $debitCentimes,
        int $creditCentimes,
    ): array {
        $erreurs = [];

        if ($ligne->getCompte() === null) {
            $erreurs[] =
                'Le compte comptable est absent.';
        }

        if ($debitCentimes < 0) {
            $erreurs[] =
                'Le débit ne peut pas être négatif.';
        }

        if ($creditCentimes < 0) {
            $erreurs[] =
                'Le crédit ne peut pas être négatif.';
        }

        if (
            $debitCentimes === 0
            && $creditCentimes === 0
        ) {
            $erreurs[] =
                'Le débit et le crédit sont tous les deux nuls.';
        }

        if (
            $debitCentimes > 0
            && $creditCentimes > 0
        ) {
            $erreurs[] =
                'Une ligne ne peut pas comporter simultanément un débit et un crédit.';
        }

        $numeroCompte = $ligne
            ->getCompte()
            ?->getNumero();

        /*
         * Pour les comptes individuels 450xxx, on conserve
         * également le copropriétaire sur l'écriture.
         *
         * Le compte collectif 450000 est exclu de ce contrôle.
         */
        if (
            $numeroCompte !== null
            && str_starts_with(
                $numeroCompte,
                '450'
            )
            && $numeroCompte !== '450000'
            && $ligne->getCoproprietaire() === null
        ) {
            $erreurs[] =
                'Le copropriétaire doit être renseigné pour un compte individuel 450.';
        }

        return $erreurs;
    }

    private function enCentimes(
        string|float|int $montant,
    ): int {
        return (int) round(
            (float) $montant * 100
        );
    }
}

