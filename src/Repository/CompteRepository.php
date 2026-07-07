<?php

namespace App\Repository;

use App\Entity\Compte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
