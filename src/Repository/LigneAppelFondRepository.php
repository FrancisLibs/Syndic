<?php

namespace App\Repository;

use App\Entity\LigneAppelFond;
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
}
