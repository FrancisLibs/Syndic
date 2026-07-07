<?php

namespace App\Repository;

use App\Entity\LigneAppelFond;
use App\Entity\Exercice;
use App\Entity\Coproprietaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneAppelFond>
 */
class LigneAppelFondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneAppelFond::class);
    }

    public function calculerTotalAppele(
        Exercice $exercice,
        Coproprietaire $coproprietaire
    ): float {
        $result = $this->createQueryBuilder('laf')
            ->select('SUM(laf.montant)')
            ->join('laf.appelFond', 'af')
            ->join('af.budget', 'b')
            ->where('b.exercice = :exercice')
            ->andWhere('laf.coproprietaire = :coproprietaire')
            ->setParameter('exercice', $exercice)
            ->setParameter('coproprietaire', $coproprietaire)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
