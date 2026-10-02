<?php

namespace App\Repository;

use App\Entity\ApprobationComptes;
use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApprobationComptes>
 */
class ApprobationComptesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ApprobationComptes::class
        );
    }

    public function findPourExercice(
        Exercice $exercice
    ): ?ApprobationComptes {
        return $this->findOneBy([
            'exercice' => $exercice,
        ]);
    }
}
