<?php

namespace App\Repository\Import;

use App\Entity\Exercice;
use App\Entity\Import\ImportFactureFournisseur;
use App\Enum\ImportStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ImportFactureFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            ImportFactureFournisseur::class
        );
    }

    /**
     * Toutes les lignes d’import d’un exercice.
     *
     * @return ImportFactureFournisseur[]
     */
    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('import')
            ->andWhere('import.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('import.dateFacture', 'ASC')
            ->addOrderBy('import.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Lignes pouvant être contrôlées ou retraitées.
     *
     * @return ImportFactureFournisseur[]
     */
    public function findATraiter(
        ?Exercice $exercice = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('import')
            ->andWhere('import.statut IN (:statuts)')
            ->setParameter('statuts', [
                ImportStatut::EN_ATTENTE,
                ImportStatut::ERREUR,
            ])
            ->orderBy('import.dateFacture', 'ASC')
            ->addOrderBy('import.id', 'ASC');

        if ($exercice !== null) {
            $queryBuilder
                ->andWhere('import.exercice = :exercice')
                ->setParameter('exercice', $exercice);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * Lignes strictement prêtes pour l’import.
     *
     * @return ImportFactureFournisseur[]
     */
    public function findEnAttente(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('import')
            ->andWhere('import.exercice = :exercice')
            ->andWhere('import.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'statut',
                ImportStatut::EN_ATTENTE
            )
            ->orderBy('import.dateFacture', 'ASC')
            ->addOrderBy('import.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function compterErreurs(
        Exercice $exercice
    ): int {
        return (int) $this->createQueryBuilder('import')
            ->select('COUNT(import.id)')
            ->andWhere('import.exercice = :exercice')
            ->andWhere('import.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'statut',
                ImportStatut::ERREUR
            )
            ->getQuery()
            ->getSingleScalarResult();
    }
}
