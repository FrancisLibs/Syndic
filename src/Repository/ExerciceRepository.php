<?php

namespace App\Repository;

use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercice>
 */
class ExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    public function findActif(): ?Exercice
    {
        return $this->findOneBy(
            [
                'actif' => true
            ]
        );
    }

    public function findSuivant(Exercice $exercice): ?Exercice
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateDebut > :date')
            ->setParameter('date', $exercice->getDateFin())
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
