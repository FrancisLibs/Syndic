<?php

namespace App\Repository;

use App\Entity\CompteurEau;
use App\Entity\Exercice;
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

    /**
     * Retourne les compteurs concernés par un exercice :
     * - les compteurs actuellement actifs ;
     * - les compteurs déposés pendant l'exercice ;
     * - les compteurs possédant déjà un relevé sur l'exercice.
     *
     * @return CompteurEau[]
     */
    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lot', 'l')
            ->addSelect('l')
            ->leftJoin(
                'c.releves',
                'r',
                'WITH',
                'r.exercice = :exercice'
            )
            ->andWhere(
                'c.actif = :actif
            OR (
                c.dateDepose >= :dateDebut
                AND c.dateDepose <= :dateFin
            )
            OR r.id IS NOT NULL'
            )
            ->setParameter('actif', true)
            ->setParameter(
                'dateDebut',
                $exercice->getDateDebut()
            )
            ->setParameter(
                'dateFin',
                $exercice->getDateFin()
            )
            ->setParameter('exercice', $exercice)
            ->orderBy('c.general', 'DESC')
            ->addOrderBy('l.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAutreActifPourLot(
        \App\Entity\Lot $lot,
        ?CompteurEau $compteurExclu = null
    ): ?CompteurEau {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.lot = :lot')
            ->andWhere('c.actif = :actif')
            ->setParameter('lot', $lot)
            ->setParameter('actif', true)
            ->setMaxResults(1);

        if ($compteurExclu?->getId() !== null) {
            $qb
                ->andWhere('c != :compteurExclu')
                ->setParameter('compteurExclu', $compteurExclu);
        }

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }
}
