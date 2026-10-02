<?php

namespace App\Repository;

use App\Entity\LotCoproprietaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LotCoproprietaire>
 */
class LotCoproprietaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotCoproprietaire::class);
    }

    public function findActifsADate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('lc')
            ->join('lc.lot', 'l')
            ->addSelect('l')
            ->join('lc.coproprietaire', 'c')
            ->addSelect('c')
            ->andWhere('lc.dateDebut <= :date')
            ->andWhere(
                'lc.dateFin IS NULL OR lc.dateFin >= :date'
            )
            ->setParameter('date', $date)
            ->orderBy('l.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculerVoixParCoproprietaire(
        \DateTimeInterface $date
    ): array {
        $relations = $this->findActifsADate($date);

        $resultat = [];

        foreach ($relations as $relation) {
            $coproprietaire = $relation->getCoproprietaire();
            $lot = $relation->getLot();

            if (!$coproprietaire || !$lot) {
                continue;
            }

            $pourcentage = (float) $relation->getPourcentage();

            $voix = (int) round(
                $lot->getTantiemes() * $pourcentage / 100
            );

            $id = $coproprietaire->getId();

            if (!isset($resultat[$id])) {
                $resultat[$id] = [
                    'coproprietaire' => $coproprietaire,
                    'nombreVoix' => 0,
                ];
            }

            $resultat[$id]['nombreVoix'] += $voix;
        }

        return array_values($resultat);
    }
}
