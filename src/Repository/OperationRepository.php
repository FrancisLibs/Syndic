<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\OperationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function findAllWithEcritures()
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.ecritures', 'e')
            ->addSelect('e')
            ->orderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existsAppelFondsForDate(
        \DateTimeInterface $date,
        Exercice $exercice
    ): bool {
        return (bool) $this->createQueryBuilder('o')
            ->select('1')
            ->where('o.type = :type')
            ->andWhere('o.date = :date')
            ->andWhere('o.exercice = :exercice')
            ->setMaxResults(1)
            ->setParameter('type', OperationType::APPEL_FONDS)
            ->setParameter('date', $date)
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByLotAndType($lot, $type)
    {
        return $this->createQueryBuilder('o')
            ->join('o.lot', 'l')
            ->join('o.ecritures', 'e')
            ->where('o.lot = :lot')
            ->andWhere('o.type = :type')
            ->setParameter('lot', $lot)
            ->setParameter('type', $type)
            ->orderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOperationsByExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.ecritures', 'e')
            ->addSelect('e')
            ->andWhere('e.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->distinct()
            ->orderBy('o.date', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function clotureComptesGestionExiste(
        Exercice $exercice
    ): bool {
        $count = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.ecritures', 'e')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', 'cloture')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findClotureComptesGestion(
        Exercice $exercice
    ): ?Operation {
        $operationId = $this->createQueryBuilder('o')
            ->select('o.id')
            ->join('o.ecritures', 'e')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', OperationType::CLOTURE)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$operationId) {
            return null;
        }

        return $this->createQueryBuilder('o')
            ->leftJoin('o.ecritures', 'e')
            ->addSelect('e')
            ->leftJoin('e.compte', 'c')
            ->addSelect('c')
            ->andWhere('o.id = :id')
            ->setParameter('id', $operationId['id'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function aNouveauxExistent(
        Exercice $exercice
    ): bool {
        $count = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.ecritures', 'e')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', OperationType::A_NOUVEAU)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findANouveaux(
        Exercice $exercice
    ): ?Operation {
        $operationId = $this->createQueryBuilder('o')
            ->select('o.id')
            ->join('o.ecritures', 'e')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', OperationType::A_NOUVEAU)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$operationId) {
            return null;
        }

        return $this->createQueryBuilder('o')
            ->leftJoin('o.ecritures', 'e')
            ->addSelect('e')
            ->leftJoin('e.compte', 'c')
            ->addSelect('c')
            ->leftJoin('e.coproprietaire', 'cp')
            ->addSelect('cp')
            ->andWhere('o.id = :id')
            ->setParameter('id', $operationId['id'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existeANouveauPourExercice(
        Exercice $exercice
    ): bool {
        $resultat = $this->createQueryBuilder('operation')
            ->select('COUNT(DISTINCT operation.id)')
            ->innerJoin('operation.ecritures', 'ecriture')
            ->where('ecriture.exercice = :exercice')
            ->andWhere('operation.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', OperationType::A_NOUVEAU)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $resultat > 0;
    }

    public function existeANouveau(): bool
    {
        return null !== $this->findOneBy([
            'type' => OperationType::A_NOUVEAU,
        ]);
    }
}
