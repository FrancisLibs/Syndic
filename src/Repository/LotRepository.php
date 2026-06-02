<?php

namespace App\Repository;

use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lot>
 */
class LotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lot::class);
    }

    //    /**
    //     * @return Lot[] Returns an array of Lot objects
    //     */

    public function findWithCopro(int $id): ?Lot
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.coproprietaire', 'c')
            ->leftJoin('l.copropriete', 'cp')
            ->addSelect('c', 'cp')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // src/Repository/LotRepository.php

    public function getEtatCompte(int $lotId)
    {
        $conn = $this->getEntityManager()->getConnection();

        // On récupère à la fois les répartitions 
        //(charges) et les écritures (paiements)
        $sql = "
            SELECT date, libelle, montant as debit, 0 as credit 
            FROM repartition r
            JOIN operation o ON o.id = (
                SELECT operation_id FROM ecriture WHERE id = r.ecriture_id
            )
            WHERE r.lot_id = :id
            
            UNION ALL
            
            SELECT o.date, o.libelle, 0 as debit, e.credit
            FROM ecriture e
            JOIN operation o ON e.operation_id = o.id
            WHERE e.lot_id = :id AND e.credit > 0
            
            ORDER BY date ASC
    ";

        return $conn->executeQuery($sql, ['id' => $lotId])->fetchAllAssociative();
    }

    public function getTotalTantiemes(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('SUM(l.tantiemes)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
