<?php

namespace App\Repository;

use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Entity\Lot;
use App\Enum\CompteType;
use App\Enum\OperationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ecriture>
 */
class EcritureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ecriture::class);
    }

    public function findByLotAndCompteType(Lot $lot, CompteType $type): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.compte', 'c')
            ->where('e.lot = :lot')
            ->andWhere('c.type = :type')
            ->setParameter('lot', $lot)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function calculerTotauxParExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.debit), 0) AS debit')
            ->addSelect('COALESCE(SUM(e.credit), 0) AS credit')
            ->andWhere('e.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleResult();
    }

    public function calculerSoldesReportables(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.id AS compteId')
            ->addSelect('cp.id AS coproprietaireId')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->leftJoin('e.coproprietaire', 'cp')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('c.type IN (:typesReportables)')
            ->setParameter('exercice', $exercice)
            ->setParameter('typesReportables', [
                CompteType::ACTIF,
                CompteType::PASSIF,
                CompteType::TIERS,
                CompteType::BANQUE,
            ])
            ->groupBy('c.id')
            ->addGroupBy('cp.id')
            ->having('SUM(e.debit) <> SUM(e.credit)')
            ->orderBy('c.numero', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerTotauxChargesProduits(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.type AS type')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('c.type IN (:types)')
            ->setParameter('exercice', $exercice)
            ->setParameter('types', [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ])
            ->groupBy('c.type')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerSoldesComptesGestion(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.id AS compteId')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('c.type IN (:types)')
            ->setParameter('exercice', $exercice)
            ->setParameter('types', [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ])
            ->groupBy('c.id')
            ->orderBy('c.numero')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerTotalCharges(
        Exercice $exercice
    ): float {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.debit) - SUM(e.credit)')
            ->join('e.compte', 'c')
            ->where('e.exercice = :exercice')
            ->andWhere('c.type = :type')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', 'charge')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Retourne les écritures de débit correspondant aux charges
     * comptabilisées pour un exercice.
     *
     * @return Ecriture[]
     */
    public function findChargesAvecRepartitions(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->addSelect(
                'operation',
                'compte',
                'repartitions',
                'typeCharge'
            )
            ->join('e.operation', 'operation')
            ->join('e.compte', 'compte')
            ->leftJoin('e.repartitions', 'repartitions')
            ->leftJoin('operation.typeCharge', 'typeCharge')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('operation.type = :type')
            ->andWhere('e.debit > 0')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', OperationType::CHARGE)
            ->orderBy('operation.date', 'ASC')
            ->addOrderBy('operation.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
