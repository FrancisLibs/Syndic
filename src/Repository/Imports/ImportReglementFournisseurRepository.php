<?php

namespace App\Repository\Imports;

use App\Entity\Exercice;
use App\Entity\Imports\ImportReglementFournisseur;
use App\Enum\ImportStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ImportReglementFournisseurRepository
extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            ImportReglementFournisseur::class
        );
    }

    /**
     * @return ImportReglementFournisseur[]
     */
    public function findEnAttente(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exercice = :exercice')
            ->andWhere('i.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'statut',
                ImportStatut::EN_ATTENTE
            )
            ->orderBy('i.dateReglement', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ImportReglementFournisseur[]
     */
    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('i.dateReglement', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function trouverDoublon(
        ImportReglementFournisseur $import
    ): ?ImportReglementFournisseur {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.exercice = :exercice')
            ->andWhere('i.fournisseur = :fournisseur')
            ->andWhere('i.dateReglement = :dateReglement')
            ->andWhere('i.montant = :montant')
            ->setParameter(
                'exercice',
                $import->getExercice()
            )
            ->setParameter(
                'fournisseur',
                $import->getFournisseur()
            )
            ->setParameter(
                'dateReglement',
                $import->getDateReglement()
            )
            ->setParameter(
                'montant',
                $import->getMontant()
            );

        if ($import->getReference() === null) {
            $qb->andWhere('i.reference IS NULL');
        } else {
            $qb
                ->andWhere('i.reference = :reference')
                ->setParameter(
                    'reference',
                    $import->getReference()
                );
        }

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }
}
