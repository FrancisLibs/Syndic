<?php

namespace App\Repository;

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
            ->join('r.ecriture', 'e')
            ->join('e.operation', 'o')
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
