<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\OperationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function findAllWithEcritures()
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.ecritures', 'e')
            ->addSelect('e')
            ->orderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existsAppelFondsForDate(
        \DateTimeInterface $date,
        Exercice $exercice
    ): bool {
        return (bool) $this->createQueryBuilder('o')
            ->select('1')
            ->where('o.type = :type')
            ->andWhere('o.date = :date')
            ->andWhere('o.exercice = :exercice')
            ->setMaxResults(1)
            ->setParameter('type', OperationType::APPEL_FONDS)
            ->setParameter('date', $date)
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByLotAndType($lot, $type)
    {
        return $this->createQueryBuilder('o')
            ->join('o.lot', 'l')
            ->join('o.ecritures', 'e')
            ->where('o.lot = :lot')
            ->andWhere('o.type = :type')
            ->setParameter('lot', $lot)
            ->setParameter('type', $type)
            ->orderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
