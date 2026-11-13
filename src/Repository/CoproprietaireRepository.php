<?php

namespace App\Repository;

use App\Entity\Coproprietaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coproprietaire>
 */
class CoproprietaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coproprietaire::class);
    }

    /**
 * Retourne les copropriétaires possédant au moins
 * un lot à la date indiquée.
 *
 * @return Coproprietaire[]
 */
public function findProprietairesADate(
    \DateTimeImmutable $date
): array {
    return $this->createQueryBuilder('c')
        ->select('DISTINCT c')
        ->innerJoin('c.lotCoproprietaires', 'lc')
        ->andWhere('lc.dateDebut <= :date')
        ->andWhere(
            '(lc.dateFin IS NULL OR lc.dateFin >= :date)'
        )
        ->setParameter('date', $date)
        ->orderBy('c.nom', 'ASC')
        ->getQuery()
        ->getResult();
}
}
