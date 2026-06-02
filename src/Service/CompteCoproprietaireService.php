<?php

namespace App\Service;

use App\Entity\Compte;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\CompteType;

class CompteCoproprietaireService
{
    public function __construct(
        private CompteRepository $compteRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function creerCompte(
        string $nom
    ): Compte {

        // =====================
        // Comptes copro existants
        // =====================

        $comptes =
            $this->compteRepository
            ->createQueryBuilder('c')

            ->where('c.numero LIKE :prefixe')

            ->setParameter(
                'prefixe',
                '450%'
            )

            ->getQuery()

            ->getResult();

        // =====================
        // Recherche max
        // =====================

        $maxNumero = 450000;

        foreach ($comptes as $compte) {

            $numero =
                (int) trim(
                    $compte->getNumero()
                );

            if ($numero > $maxNumero) {

                $maxNumero = $numero;
            }
        }

        // =====================
        // Nouveau numéro
        // =====================

        $numero =
            $maxNumero + 1;

        $numeroFormate =
            str_pad(
                (string) $numero,
                6,
                '0',
                STR_PAD_LEFT
            );

        // =====================
        // Création compte
        // =====================

        $compte = new Compte();

        $compte->setType(
            CompteType::TIERS
        );

        $compte->setNumero(
            $numeroFormate
        );

        $compte->setLibelle(
            trim($nom)
        );

        $this->entityManager
            ->persist($compte);

        return $compte;
    }
}
