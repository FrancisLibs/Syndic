<?php

namespace App\Service;

use App\Entity\Coproprietaire;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\ExerciceRepository;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerationANouveauService
{
    public function __construct(
        private readonly ComptabiliteService $comptabiliteService,
        private readonly CompteRepository $compteRepository,
        private readonly ExerciceRepository $exerciceRepository,
        private readonly OperationRepository $operationRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Génère l'écriture comptable d'ouverture du premier exercice.
     *
     * Pour les soldes des copropriétaires :
     * - montant positif : solde créditeur, donc écriture au crédit ;
     * - montant négatif : solde débiteur, donc écriture au débit.
     *
     * Structure attendue :
     *
     * [
     *     [
     *         'coproprietaire' => $coproprietaire,
     *         'montant' => 246.66,
     *     ],
     * ]
     *
     * @param array<int, array{
     *     coproprietaire: Coproprietaire,
     *     montant: float|int|string
     * }> $soldesCoproprietaires
     */
    public function generer(
        \DateTimeImmutable $date,
        float $soldeBanque,
        float $fondsRoulement,
        array $soldesCoproprietaires,
    ): void {
        if ($this->operationRepository->existeANouveau()) {
            throw new \RuntimeException(
                'Une opération d\'à-nouveau existe déjà.'
            );
        }

        $exercice = $this->exerciceRepository->findActif();

        if (!$exercice) {
            throw new \RuntimeException(
                'Aucun exercice actif n\'a été trouvé.'
            );
        }

        if ($exercice->isCloture()) {
            throw new \RuntimeException(
                'Impossible de générer les à-nouveaux sur un exercice clôturé.'
            );
        }

        $compteBanque = $this->compteRepository->findOneBy([
            'numero' => '512000',
        ]);

        if (!$compteBanque) {
            throw new \RuntimeException(
                'Le compte bancaire 512000 est introuvable.'
            );
        }

        $compteFondsRoulement = $this->compteRepository->findOneBy([
            'numero' => '102000',
        ]);

        if (!$compteFondsRoulement) {
            throw new \RuntimeException(
                'Le compte d\'avance de trésorerie 102000 est introuvable.'
            );
        }

        $soldeBanque = round($soldeBanque, 2);
        $fondsRoulement = round($fondsRoulement, 2);

        if ($soldeBanque <= 0) {
            throw new \InvalidArgumentException(
                'Le solde bancaire doit être supérieur à zéro.'
            );
        }

        if ($fondsRoulement <= 0) {
            throw new \InvalidArgumentException(
                'Le fonds de roulement doit être supérieur à zéro.'
            );
        }

        $operation = $this->comptabiliteService->creerOperation(
            date: $date,
            libelle: 'Écriture d\'ouverture',
            type: OperationType::A_NOUVEAU,
            piece: 'AN-' . $date->format('Y'),
        );

        // Solde bancaire positif : actif au débit.
        $this->comptabiliteService->creerDebit(
            operation: $operation,
            exercice: $exercice,
            compte: $compteBanque,
            montant: $soldeBanque,
        );

        // Avance de trésorerie : ressource au crédit.
        $this->comptabiliteService->creerCredit(
            operation: $operation,
            exercice: $exercice,
            compte: $compteFondsRoulement,
            montant: $fondsRoulement,
        );

        foreach ($soldesCoproprietaires as $index => $ligne) {
            $coproprietaire = $ligne['coproprietaire'] ?? null;
            $montant = $ligne['montant'] ?? null;

            if (!$coproprietaire instanceof Coproprietaire) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Le copropriétaire de la ligne %d est invalide.',
                        $index + 1
                    )
                );
            }

            if (!is_numeric($montant)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Le montant de la ligne %d est invalide.',
                        $index + 1
                    )
                );
            }

            $montant = round((float) $montant, 2);

            if (abs($montant) < 0.01) {
                continue;
            }

            $compteCoproprietaire = $coproprietaire->getCompte();

            if (!$compteCoproprietaire) {
                throw new \RuntimeException(
                    sprintf(
                        'Aucun compte comptable n\'est associé au copropriétaire "%s".',
                        (string) $coproprietaire
                    )
                );
            }

            /*
             * Selon le sens de ton tableau Excel :
             *
             * montant positif :
             * la copropriété doit cette somme au copropriétaire,
             * donc crédit du compte copropriétaire.
             *
             * montant négatif :
             * le copropriétaire doit cette somme à la copropriété,
             * donc débit du compte copropriétaire.
             */
            if ($montant > 0) {
                $this->comptabiliteService->creerCredit(
                    operation: $operation,
                    exercice: $exercice,
                    compte: $compteCoproprietaire,
                    montant: $montant,
                    coproprietaire: $coproprietaire,
                );

                continue;
            }

            $this->comptabiliteService->creerDebit(
                operation: $operation,
                exercice: $exercice,
                compte: $compteCoproprietaire,
                montant: abs($montant),
                coproprietaire: $coproprietaire,
            );
        }

        $this->comptabiliteService->verifierEquilibre(
            $operation
        );

        $this->em->flush();
    }
}
