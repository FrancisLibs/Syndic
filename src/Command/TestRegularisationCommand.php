<?php

namespace App\Command;

use App\Repository\ExerciceRepository;
use App\Service\Cloture\SimulationRegularisationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-regu', description: 'Simule la régularisation des charges de l’exercice 2025')]
class TestRegularisationCommand extends Command
{
    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private SimulationRegularisationService $simulationRegularisationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // On récupère l'exercice 2025 (ID 1 dans ton dump)
        $exercice = $this->exerciceRepository->find(1);

        if (!$exercice) {
            $io->error("Exercice 2025 introuvable.");
            return Command::FAILURE;
        }

        $io->title("Simulation de la régularisation : " . $exercice->getNom());

        $simulation = $this->simulationRegularisationService->simulerRegularisation($exercice);
        $io->text("Total des charges réelles de l'exercice : " . $simulation['totalChargesGlobales'] . " €");
        $io->newLine();

        $rows = [];
        foreach ($simulation['details'] as $detail) {
            $rows[] = [
                $detail['coproprietaire']->getNom() . ' ' . $detail['coproprietaire']->getPrenom(),
                $detail['tantiemes'],
                $detail['totalAppele'] . ' €',
                $detail['quotePartReelle'] . ' €',
                ($detail['resultat'] >= 0 ? '+' : '') . $detail['resultat'] . ' €'
            ];
        }

        $io->table(
            ['Copropriétaire', 'Tantièmes', 'Total Appelé', 'Quote-part Réelle', 'Régularisation'],
            $rows
        );

        return Command::SUCCESS;
    }
}
