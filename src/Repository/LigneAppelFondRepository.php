<?php

namespace App\Repository;

use App\Entity\LigneAppelFond;
use App\Entity\Exercice;
use App\Entity\Coproprietaire;
use App\Entity\Travaux\Chantier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneAppelFond>
 */
class LigneAppelFondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneAppelFond::class);
    }

    public function calculerTotalAppele(
        Exercice $exercice,
        Coproprietaire $coproprietaire
    ): float {
        $result = $this->createQueryBuilder('laf')
            ->select('SUM(laf.montant)')
            ->join('laf.appelFond', 'af')
            ->join('af.budget', 'b')
            ->where('b.exercice = :exercice')
            ->andWhere('laf.coproprietaire = :coproprietaire')
            ->setParameter('exercice', $exercice)
            ->setParameter('coproprietaire', $coproprietaire)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Lignes de charges courantes restant à régler
     * pour un copropriétaire et un exercice.
     *
     * @return LigneAppelFond[]
     */
    public function findLignesChargesARegler(
        Coproprietaire $coproprietaire,
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('l')
            ->join('l.appelFond', 'a')
            ->join('a.budget', 'b')
            ->where('l.coproprietaire = :coproprietaire')
            ->andWhere('l.soldee = false')
            ->andWhere('a.chantier IS NULL')
            ->andWhere('b.exercice = :exercice')
            ->setParameter('coproprietaire', $coproprietaire)
            ->setParameter('exercice', $exercice)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Lignes restant à régler pour un chantier.
     * Le chantier peut couvrir plusieurs exercices.
     *
     * @return LigneAppelFond[]
     */
    public function findLignesChantierARegler(
        Coproprietaire $coproprietaire,
        Chantier $chantier
    ): array {
        return $this->createQueryBuilder('l')
            ->join('l.appelFond', 'a')
            ->where('l.coproprietaire = :coproprietaire')
            ->andWhere('l.soldee = false')
            ->andWhere('a.chantier = :chantier')
            ->setParameter('coproprietaire', $coproprietaire)
            ->setParameter('chantier', $chantier)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
