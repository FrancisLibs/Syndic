<?php

namespace App\Repository;

use App\Entity\Compte;
use App\Enum\CompteType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Compte>
 */
class CompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compte::class);
    }

    public function findByNumero(string $numero): ?Compte
    {
        return $this->findOneBy([
            'numero' => $numero,
        ]);
    }

    public function findByNumeroOrFail(
        string $numero
    ): Compte {

        $compte = $this->findOneBy([
            'numero' => $numero,
        ]);

        if (!$compte) {
            throw new \LogicException(
                sprintf(
                    'Le compte %s est introuvable.',
                    $numero
                )
            );
        }

        return $compte;
    }

    public function createBilanQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.type NOT IN (:types)')
            ->setParameter('types', [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ])
            ->orderBy('c.numero', 'ASC');
    }
}
