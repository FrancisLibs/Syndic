<?php

namespace App\Repository;

use App\Entity\MandatCopropriete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Copropriete;
use App\Enum\FonctionCopropriete;

/**
 * @extends ServiceEntityRepository<MandatCopropriete>
 */
class MandatCoproprieteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MandatCopropriete::class);
    }

    public function findSyndicAlaDate(
        Copropriete $copropriete,
        \DateTimeImmutable $date
    ): ?MandatCopropriete {
        return $this->createQueryBuilder('m')
            ->andWhere('m.copropriete = :copropriete')
            ->andWhere('m.fonction = :fonction')
            ->andWhere('m.dateDebut <= :date')
            ->andWhere(
                '(m.dateFin IS NULL OR m.dateFin >= :date)'
            )
            ->setParameter('copropriete', $copropriete)
            ->setParameter(
                'fonction',
                FonctionCopropriete::SYNDIC
            )
            ->setParameter('date', $date)
            ->orderBy('m.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return MandatCopropriete[] Returns an array of MandatCopropriete objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MandatCopropriete
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
