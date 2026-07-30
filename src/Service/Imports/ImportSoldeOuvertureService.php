<?php

namespace App\Service\Imports;

use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Enum\ImportEtat;
use App\Enum\OperationType;
use App\Repository\Imports\ImportSoldeOuvertureRepository;
use App\Service\ComptabiliteService;
use Doctrine\ORM\EntityManagerInterface;

final class ImportSoldeOuvertureService
{
    public function __construct(
        private readonly ImportSoldeOuvertureRepository $repository,
        private readonly ControleImportSoldeOuvertureService $controleService,
        private readonly ComptabiliteService $comptabiliteService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function importer(
        Exercice $exercice,
    ): void {
        if ($exercice->isCloture()) {
            throw new \RuntimeException(
                'Un exercice clôturé ne peut pas recevoir de soldes d’ouverture.'
            );
        }

        if ($this->operationANouveauExiste($exercice)) {
            throw new \RuntimeException(
                sprintf(
                    'Une opération d’à-nouveaux existe déjà pour l’exercice « %s ».',
                    $exercice->getNom()
                )
            );
        }

        /*
         * Le contrôle est rejoué au moment de l’import.
         * On ne se contente pas du contrôle affiché précédemment.
         */
        $controle = $this->controleService->controler(
            $exercice
        );

        if ($controle['nombreLignes'] === 0) {
            throw new \RuntimeException(
                'Aucune ligne de solde d’ouverture n’est disponible.'
            );
        }

        if ($controle['nombreErreurs'] > 0) {
            throw new \RuntimeException(
                'L’import est impossible : certaines lignes comportent des erreurs.'
            );
        }

        if (!$controle['equilibre']) {
            throw new \RuntimeException(
                sprintf(
                    'L’import est impossible : les écritures ne sont pas équilibrées. Écart : %.2f €.',
                    $controle['ecart']
                )
            );
        }

        $lignes = $this->repository->findAImporter(
            $exercice
        );

        if ($lignes === []) {
            throw new \RuntimeException(
                'Aucune ligne en attente d’import n’a été trouvée.'
            );
        }

        $connection = $this->entityManager
            ->getConnection();

        $connection->beginTransaction();

        try {
            $operation = $this->comptabiliteService
                ->creerOperation(
                    $exercice->getDateDebut(),
                    sprintf(
                        'À-nouveaux %s',
                        $exercice->getNom()
                    ),
                    OperationType::A_NOUVEAU,
                    sprintf(
                        'AN-%s',
                        $exercice->getNom()
                    )
                );

            foreach ($lignes as $ligne) {
                if (
                    $ligne->getEtat()
                    !== ImportEtat::A_IMPORTER
                ) {
                    continue;
                }

                $compte = $ligne->getCompte();

                if ($compte === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Le compte est absent sur la ligne d’import n°%d.',
                            $ligne->getId()
                        )
                    );
                }

                $debit = (float) $ligne->getDebit();
                $credit = (float) $ligne->getCredit();

                if ($debit > 0) {
                    $this->comptabiliteService
                        ->creerDebit(
                            $operation,
                            $exercice,
                            $compte,
                            $debit,
                            $ligne->getCoproprietaire()
                        );
                } elseif ($credit > 0) {
                    $this->comptabiliteService
                        ->creerCredit(
                            $operation,
                            $exercice,
                            $compte,
                            $credit,
                            $ligne->getCoproprietaire()
                        );
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'La ligne d’import n°%d ne contient aucun montant.',
                            $ligne->getId()
                        )
                    );
                }

                $ligne
                    ->setOperation($operation)
                    ->marquerImporte();
            }

            $this->comptabiliteService
                ->verifierEquilibre($operation);

            $this->comptabiliteService
                ->enregistrer();

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            /*
             * Après le rollback SQL, on détache les objets
             * éventuellement modifiés en mémoire.
             */
            $this->entityManager->clear();

            throw $exception;
        }
    }

    private function operationANouveauExiste(
        Exercice $exercice,
    ): bool {
        $nombre = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(ecriture.id)')
            ->from(Ecriture::class, 'ecriture')
            ->innerJoin(
                'ecriture.operation',
                'operation'
            )
            ->andWhere(
                'ecriture.exercice = :exercice'
            )
            ->andWhere(
                'operation.type = :type'
            )
            ->setParameter(
                'exercice',
                $exercice
            )
            ->setParameter(
                'type',
                OperationType::A_NOUVEAU
            )
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $nombre > 0;
    }
}
