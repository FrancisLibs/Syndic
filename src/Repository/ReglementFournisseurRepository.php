<?php

namespace App\Repository;

use App\Entity\ReglementFournisseur;
use App\Entity\Fournisseur;
use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ReglementFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            ReglementFournisseur::class
        );
    }

    /**
     * @return ReglementFournisseur[]
     */
    public function findPourFournisseur(
        Fournisseur $fournisseur
    ): array {
        return $this->createQueryBuilder('r')
            ->andWhere('r.fournisseur = :fournisseur')
            ->setParameter(
                'fournisseur',
                $fournisseur
            )
            ->orderBy(
                'r.dateReglement',
                'ASC'
            )
            ->addOrderBy(
                'r.id',
                'ASC'
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ReglementFournisseur[]
     */
    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.fournisseur', 'f')
            ->addSelect('f')
            ->andWhere('r.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('r.dateReglement', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvecOperationPourCorrection(
        int $id
    ): ?ReglementFournisseur {
        return $this->createQueryBuilder('r')
            ->addSelect('operation', 'ecritures')
            ->leftJoin('r.operation', 'operation')
            ->leftJoin('operation.ecritures', 'ecritures')
            ->andWhere('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
