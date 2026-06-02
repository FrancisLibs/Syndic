<?php

namespace App\Command;

use App\Entity\Compte;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-fournisseur-comptes',
    description: 'Create account numbers for all fournisseurs',
)]

class CreateFournisseurComptesCommand extends Command
{
    public function __construct(
        private FournisseurRepository $fournisseurRepo,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $fournisseurs = $this->fournisseurRepo->findAll();

        foreach ($fournisseurs as $fournisseur) {

            $fournisseurId = $fournisseur->getId();

            $compte = new Compte();
            $compte->setNumero(
                '401' . str_pad(
                    $fournisseurId,
                    3,
                    '0',
                    STR_PAD_LEFT
                )
            );
            $compte->setLibelle(
                'Fournisseur ' . $fournisseur->getNom()
            );
            $compte->setType(\App\Enum\CompteType::TIERS);
            $fournisseur->setCompte($compte);

            $this->em->persist($compte);
        }
        $this->em->flush();
    }
}
