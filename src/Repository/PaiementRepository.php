<?php

namespace App\Repository;

use App\Entity\Paiement;
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

    public function findPaiementsValides(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.operation', 'o') // On joint l'opération
            // ->where('o.statut = :statut') // On filtre sur le statut
            // ->setParameter('statut', \App\Enum\OperationStatut::VALIDE)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
