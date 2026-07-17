<?php

namespace App\Repository;

use App\Entity\CompteurEau;
use App\Entity\Exercice;
use App\Entity\ReleveCompteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReleveCompteurRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            ReleveCompteur::class
        );
    }

    public function findByCompteurAndExercice(
        CompteurEau $compteur,
        Exercice $exercice
    ): ?ReleveCompteur {
        return $this->createQueryBuilder('r')
            ->andWhere('r.compteur = :compteur')
            ->andWhere('r.exercice = :exercice')
            ->setParameter('compteur', $compteur)
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDernierAvantExercice(
        CompteurEau $compteur,
        Exercice $exercice
    ): ?ReleveCompteur {
        return $this->createQueryBuilder('r')
            ->andWhere('r.compteur = :compteur')
            ->andWhere('r.dateReleve < :dateDebut')
            ->setParameter('compteur', $compteur)
            ->setParameter(
                'dateDebut',
                $exercice->getDateDebut()
            )
            ->orderBy('r.dateReleve', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ReleveCompteur[]
     */
    public function findByExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.compteur', 'c')
            ->addSelect('c')
            ->leftJoin('c.lot', 'l')
            ->addSelect('l')
            ->andWhere('r.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('c.general', 'DESC')
            ->addOrderBy('l.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
