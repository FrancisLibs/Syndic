<?php

namespace App\Repository;

use App\Entity\Ecriture;
use App\Entity\Lot;
use App\Enum\CompteType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ecriture>
 */
class EcritureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ecriture::class);
    }

    public function findByLotAndCompteType(Lot $lot, CompteType $type): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.compte', 'c')
            ->where('e.lot = :lot')
            ->andWhere('c.type = :type')
            ->setParameter('lot', $lot)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
