<?php

namespace App\Command;

use App\Entity\Compte;
use App\Enum\CompteType;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-plan-comptable',
    description: 'Initialise le plan comptable de base',
)]
class InitPlanComptableCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
    ) {
        parent::__construct();
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $comptes = [

            // =====================
            // Classe 1
            // =====================

            [
                'numero' => '102000',
                'libelle' => 'Fonds de réserve',
                'type' => CompteType::PASSIF,
            ],

            [
                'numero' => '489000',
                'libelle' => 'Excédent ou insuffisance sur charges courantes',
                'type' => CompteType::PASSIF,
            ],

            // =====================
            // Classe 4
            // =====================

            [
                'numero' => '401000',
                'libelle' => 'Fournisseurs',
                'type' => CompteType::TIERS,
            ],

            [
                'numero' => '450000',
                'libelle' => 'Copropriétaires',
                'type' => CompteType::TIERS,
            ],

            [
                'numero' => '512000',
                'libelle' => 'Banque',
                'type' => CompteType::BANQUE,
            ],

            // =====================
            // Classe 6
            // =====================

            [
                'numero' => '601000',
                'libelle' => 'Eau',
                'type' => CompteType::CHARGE,
            ],

            [
                'numero' => '602000',
                'libelle' => 'Électricité',
                'type' => CompteType::CHARGE,
            ],

            [
                'numero' => '603000',
                'libelle' => 'Entretien',
                'type' => CompteType::CHARGE,
            ],

            [
                'numero' => '604000',
                'libelle' => 'Assurance',
                'type' => CompteType::CHARGE,
            ],

            [
                'numero' => '605000',
                'libelle' => 'Honoraires syndic',
                'type' => CompteType::CHARGE,
            ],

            [
                'numero' => '606000',
                'libelle' => 'Travaux',
                'type' => CompteType::CHARGE,
            ],

            // =====================
            // Classe 7
            // =====================

            [
                'numero' => '701000',
                'libelle' => 'Appels de fonds',
                'type' => CompteType::PRODUIT,
            ],

            [
                'numero' => '708000',
                'libelle' => 'Produits divers',
                'type' => CompteType::PRODUIT,
            ],
        ];

        foreach ($comptes as $data) {

            $existant =
                $this->compteRepository
                ->findOneBy(
                    [
                        'numero' => $data['numero']
                    ]
                );

            if ($existant) {
                continue;
            }

            $compte = new Compte();

            $compte->setNumero(
                $data['numero']
            );

            $compte->setLibelle(
                $data['libelle']
            );

            $compte->setType(
                $data['type']
            );

            $this->entityManager
                ->persist($compte);
        }

        $this->entityManager->flush();

        $output->writeln(
            '<info>Plan comptable initialisé</info>'
        );

        return Command::SUCCESS;
    }
}
