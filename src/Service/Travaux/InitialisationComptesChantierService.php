<?php

namespace App\Service\Travaux;

use App\Entity\Compte;
use App\Entity\Travaux\Chantier;
use App\Enum\CompteType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class InitialisationComptesChantierService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CompteRepository $compteRepository,
    ) {}

    public function initialiser(Chantier $chantier): void
    {
        if (
            $chantier->getCompteProvision() !== null
            || $chantier->getCompteCharge() !== null
            || $chantier->getCompteProduit() !== null
        ) {
            throw new \LogicException(
                'Les comptes de ce chantier ont déjà été initialisés.'
            );
        }

        $suffixe = $this->determinerProchainSuffixe();

        $numeroProvision = '102' . $suffixe;
        $numeroCharge = '671' . $suffixe;
        $numeroProduit = '702' . $suffixe;

        $this->verifierNumeroDisponible($numeroProvision);
        $this->verifierNumeroDisponible($numeroCharge);
        $this->verifierNumeroDisponible($numeroProduit);

        $compteProvision = $this->creerCompte(
            $numeroProvision,
            'Provisions travaux - ' . $chantier->getLibelle(),
            CompteType::PASSIF
        );

        $compteCharge = $this->creerCompte(
            $numeroCharge,
            'Travaux - ' . $chantier->getLibelle(),
            CompteType::CHARGE
        );

        $compteProduit = $this->creerCompte(
            $numeroProduit,
            'Provisions travaux - ' . $chantier->getLibelle(),
            CompteType::PRODUIT
        );

        $chantier
            ->setCompteProvision($compteProvision)
            ->setCompteCharge($compteCharge)
            ->setCompteProduit($compteProduit);

        $this->em->flush();
    }

    private function determinerProchainSuffixe(): string
    {
        for ($numero = 1; $numero <= 999; $numero++) {

            $suffixe = str_pad(
                (string) $numero,
                3,
                '0',
                STR_PAD_LEFT
            );

            if (
                !$this->compteExiste('102' . $suffixe)
                && !$this->compteExiste('671' . $suffixe)
                && !$this->compteExiste('702' . $suffixe)
            ) {
                return $suffixe;
            }
        }

        throw new \LogicException(
            'Aucun numéro de compte disponible pour un nouveau chantier.'
        );
    }

    private function compteExiste(string $numero): bool
    {
        return $this->compteRepository->findOneBy([
            'numero' => $numero,
        ]) !== null;
    }

    private function verifierNumeroDisponible(string $numero): void
    {
        if ($this->compteExiste($numero)) {
            throw new \LogicException(
                sprintf(
                    'Le compte %s existe déjà.',
                    $numero
                )
            );
        }
    }

    private function creerCompte(
        string $numero,
        string $libelle,
        CompteType $type,
    ): Compte {
        $compte = new Compte();

        $compte
            ->setNumero($numero)
            ->setLibelle($libelle)
            ->setType($type);

        $this->em->persist($compte);

        return $compte;
    }
}
