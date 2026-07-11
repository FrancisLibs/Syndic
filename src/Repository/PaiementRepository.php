<?php

namespace App\Repository;

use App\Entity\Paiement;
use App\Entity\Exercice;
use App\Enum\OperationStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    // src/Repository/PaiementRepository.php

    public function findPaiementsValidesByExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('p.datePaiement', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
