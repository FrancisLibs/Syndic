<?php

namespace App\Repository;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Entity\Repartition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Repartition>
 */
class RepartitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repartition::class);
    }

    /**
     * @return Repartition[] Returns an array of Repartition objects
     */
    public function findByLot($lot): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.lot = :lot')
            ->setParameter('lot', $lot)
            ->leftjoin('r.ecriture', 'e')
            ->join('e.operation', 'o')
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculerTotalPourCoproprietaireEtExercice(
        Coproprietaire $coproprietaire,
        Exercice $exercice
    ): float {
        $resultat = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montant), 0)')
            ->andWhere('r.coproprietaire = :coproprietaire')
            ->andWhere('r.exercice = :exercice')
            ->setParameter('coproprietaire', $coproprietaire)
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $resultat;
    }

    /**
     * @return Repartition[]
     */
    public function findPourExerciceAvecDetails(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('r')
            ->addSelect('coproprietaire', 'lot', 'ecriture', 'compte', 'operation')
            ->join('r.coproprietaire', 'coproprietaire')
            ->join('r.lot', 'lot')
            ->leftJoin('r.ecriture', 'ecriture')
            ->leftJoin('ecriture.compte', 'compte')
            ->leftJoin('ecriture.operation', 'operation')
            ->andWhere('r.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('coproprietaire.id', 'ASC')
            ->addOrderBy('lot.reference', 'ASC')
            ->addOrderBy('compte.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
