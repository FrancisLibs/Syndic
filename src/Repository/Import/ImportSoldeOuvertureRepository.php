<?php

namespace App\Repository\Import;

use App\Entity\Exercice;
use App\Entity\Import\ImportSoldeOuverture;
use App\Enum\ImportEtat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ImportSoldeOuvertureRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            ImportSoldeOuverture::class
        );
    }

    /**
     * @return ImportSoldeOuverture[]
     */
    public function findPourExercice(
        Exercice $exercice,
    ): array {
        return $this->createQueryBuilder('import')
            ->leftJoin('import.compte', 'compte')
            ->addSelect('compte')
            ->leftJoin(
                'import.coproprietaire',
                'coproprietaire'
            )
            ->addSelect('coproprietaire')
            ->andWhere(
                'import.exercice = :exercice'
            )
            ->setParameter(
                'exercice',
                $exercice
            )
            ->orderBy(
                'import.id',
                'ASC'
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ImportSoldeOuverture[]
     */
    public function findAImporter(
        Exercice $exercice,
    ): array {
        return $this->createQueryBuilder('import')
            ->leftJoin('import.compte', 'compte')
            ->addSelect('compte')
            ->leftJoin(
                'import.coproprietaire',
                'coproprietaire'
            )
            ->addSelect('coproprietaire')
            ->andWhere(
                'import.exercice = :exercice'
            )
            ->andWhere(
                'import.etat = :etat'
            )
            ->setParameter(
                'exercice',
                $exercice
            )
            ->setParameter(
                'etat',
                ImportEtat::A_IMPORTER
            )
            ->orderBy(
                'import.id',
                'ASC'
            )
            ->getQuery()
            ->getResult();
    }
}
