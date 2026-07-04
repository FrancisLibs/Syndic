<?php

namespace App\Service;

use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Entity\Operation;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Service\CalculRegularisationService;
use Doctrine\ORM\EntityManagerInterface;

class GenerationRegularisationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CompteRepository $compteRepository,
        private CalculRegularisationService $calculRegularisationService,
    ) {}

    public function generer(
        Exercice $exercice
    ): void {

        if ($exercice->isRegularisationsGenerees()) {
            throw new \RuntimeException(
                'Les régularisations ont déjà été générées.'
            );
        }

        $regularisations =
            $this->calculRegularisationService
            ->calculer($exercice);

        $compteResultat =
            $this->compteRepository->findOneBy([
                'numero' => '120000'
            ]);

        if (!$compteResultat) {
            throw new \RuntimeException(
                'Compte 120000 introuvable'
            );
        }

        foreach ($regularisations as $ligne) {

            $copro = $ligne['coproprietaire'];

            $montant =
                round(
                    (float) $ligne['regularisation'],
                    2
                );

            if (abs($montant) < 0.01) {
                continue;
            }

            $compteCopro = $copro->getCompte();

            if (!$compteCopro) {
                continue;
            }

            $operation = new Operation();

            $operation->setDate(
                new \DateTimeImmutable()
            );

            $operation->setLibelle(
                'Régularisation ' .
                    $exercice->getNom() .
                    ' - ' .
                    $copro
            );

            $operation->setType(
                OperationType::REGULARISATION
            );

            if ($montant > 0) {

                // copro débiteur

                $debit = new Ecriture();

                $debit->setCompte(
                    $compteCopro
                );

                $debit->setDebit(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

                $debit->setCredit('0.00');

                $debit->setDate(
                    $operation->getDate()
                );

                $debit->setOperation(
                    $operation
                );

                $debit->setExercice(
                    $exercice
                );

                $credit = new Ecriture();

                $credit->setCompte(
                    $compteResultat
                );

                $credit->setDebit('0.00');

                $credit->setCredit(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

                $credit->setDate(
                    $operation->getDate()
                );

                $credit->setOperation(
                    $operation
                );

                $credit->setExercice(
                    $exercice
                );
            } else {

                $montant = abs($montant);

                // copro créditeur

                $debit = new Ecriture();

                $debit->setCompte(
                    $compteResultat
                );

                $debit->setDebit(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

                $debit->setCredit('0.00');

                $debit->setDate(
                    $operation->getDate()
                );

                $debit->setOperation(
                    $operation
                );

                $debit->setExercice(
                    $exercice
                );

                $credit = new Ecriture();

                $credit->setCompte(
                    $compteCopro
                );

                $credit->setDebit('0.00');

                $credit->setCredit(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

                $credit->setDate(
                    $operation->getDate()
                );

                $credit->setOperation(
                    $operation
                );

                $credit->setExercice(
                    $exercice
                );
            }

            $operation->addEcriture(
                $debit
            );

            $operation->addEcriture(
                $credit
            );

            $this->em->persist(
                $operation
            );
        }

        $exercice->setRegularisationsGenerees(true);

        $this->em->flush();
    }
}
