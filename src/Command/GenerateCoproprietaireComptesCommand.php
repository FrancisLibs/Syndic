<?php

namespace App\Command;

use App\Repository\CoproprietaireRepository;
use App\Service\CompteCoproprietaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-copro-comptes'
)]
class GenerateCoproprietaireComptesCommand extends Command
{
    public function __construct(
        private CoproprietaireRepository $coproprietaireRepository,
        private CompteCoproprietaireService $compteService,
        private EntityManagerInterface $entityManager,
    ) {

        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $coproprietaires = $this->coproprietaireRepository->findAll();

        foreach ($coproprietaires as $coproprietaire) {

            // Déjà un compte
            if ($coproprietaire->getCompte()) {
                continue;
            }

            $nom = trim(
                $coproprietaire->getNom() . ' ' . $coproprietaire->getPrenom()
            );

            $compte = $this->compteService->creerCompte($nom);

            $coproprietaire->setCompte($compte);

            $this->entityManager->flush();

            $output->writeln(
                sprintf(
                    'Compte %s créé pour %s',
                    $compte->getNumero(),
                    $nom
                )
            );
        }

        $output->writeln('Terminé.');

        return Command::SUCCESS;
    }
}
