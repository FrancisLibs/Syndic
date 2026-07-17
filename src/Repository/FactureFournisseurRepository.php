<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


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
            ->innerJoin('f.exercice', 'e')
            ->addSelect('e')
            ->andWhere(
                'e = :exercice
            OR (
                e.dateFin < :dateDebut
                AND f.soldee = :nonSoldee
            )'
            )
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'dateDebut',
                $exercice->getDateDebut()
            )
            ->setParameter('nonSoldee', false)
            ->orderBy('e.dateDebut', 'DESC')
            ->addOrderBy('f.soldee', 'ASC')
            ->addOrderBy('f.dateEcheance', 'ASC')
            ->addOrderBy('f.dateFacture', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FactureFournisseur[]
     */
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
            ->select('COALESCE(SUM(facture.montant), 0)')
            ->innerJoin('facture.typeCharge', 'typeCharge')
            ->andWhere('facture.exercice = :exercice')
            ->andWhere('typeCharge.estEau = true')
            ->andWhere('facture.comptabilisee = true')
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
