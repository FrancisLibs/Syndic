<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\FactureFournisseurType;


class FactureFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureFournisseur::class);
    }

    // src/Repository/FactureFournisseurRepository.php

    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.typeCharge', 'tc')
            ->addSelect('tc')
            ->andWhere('f.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('tc.nom', 'ASC')
            ->addOrderBy('f.dateFacture', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FactureFournisseur[]
     */
    public function findFacturesEauByExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('facture')
            ->innerJoin('facture.typeCharge', 'typeCharge')
            ->addSelect('typeCharge')
            ->andWhere('facture.exercice = :exercice')
            ->andWhere('typeCharge.estEau = :estEau')
            ->andWhere('facture.comptabilisee = :comptabilisee')
            ->setParameter('exercice', $exercice)
            ->setParameter('estEau', true)
            ->setParameter('comptabilisee', true)
            ->orderBy('facture.dateFacture', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculerTotalFacturesEau(
        Exercice $exercice
    ): string {
        return (string) $this->createQueryBuilder('facture')
            ->select(
                'COALESCE(SUM(
                CASE
                    WHEN facture.type = :typeAvoir
                    THEN -facture.montant
                    ELSE facture.montant
                END
            ), 0)'
            )
            ->innerJoin('facture.typeCharge', 'typeCharge')
            ->andWhere('facture.exercice = :exercice')
            ->andWhere('typeCharge.estEau = true')
            ->andWhere('facture.comptabilisee = true')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'typeAvoir',
                FactureFournisseurType::AVOIR
            )
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAvecOperationPourCorrection(
        int $id
    ): ?FactureFournisseur {
        return $this->createQueryBuilder('f')
            ->addSelect('o', 'e', 'r')
            ->leftJoin('f.operation', 'o')
            ->leftJoin('o.ecritures', 'e')
            ->leftJoin('e.repartitions', 'r')
            ->andWhere('f.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
