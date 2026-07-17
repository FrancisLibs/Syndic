<?php

namespace App\Repository;

use App\Entity\CompteurEau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompteurEau>
 */
class CompteurEauRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            CompteurEau::class
        );
    }

    public function findCompteurGeneralActif(): ?CompteurEau
    {
        return $this->findOneBy([
            'general' => true,
            'actif' => true,
        ]);
    }

    /**
     * @return CompteurEau[]
     */
    public function findIndividuelsActifs(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.lot', 'l')
            ->addSelect('l')
            ->andWhere('c.general = :general')
            ->andWhere('c.actif = :actif')
            ->setParameter('general', false)
            ->setParameter('actif', true)
            ->orderBy('l.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CompteurEau[]
     */
    public function findTousActifs(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lot', 'l')
            ->addSelect('l')
            ->andWhere('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.general', 'DESC')
            ->addOrderBy('l.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
