<?php

namespace App\Repository;

use App\Entity\AppelFond;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppelFond>
 */
class AppelFondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppelFond::class);
    }

    public function countForYear(int $year): int
    {
        // On crée les bornes : 1er janvier et 31 décembre de l'année
        $dateDebut = new \DateTime("$year-01-01 00:00:00");
        $dateFin = new \DateTime("$year-12-31 23:59:59");

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.dateAppel >= :debut')
            ->andWhere('a.dateAppel <= :fin')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult(); // Renvoie directement le chiffre (ex: 3)
    }
}
