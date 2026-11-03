<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerationAppelFondService
{
    public function __construct(
        private CompteRepository $compteRepository,
        private ComptabiliteService $comptabiliteService,
        private EntityManagerInterface $entityManager,
        private EcritureRepository $ecritureRepository,
    ) {}

    public function generer(AppelFond $appelFond): void
    {
        $exercice = $appelFond->getBudget()->getExercice();

        $compteProduit = $this->compteRepository
            ->findByNumeroOrFail('701000');

        $operation = $this->comptabiliteService->creerOperation(
            $appelFond->getDateAppel(),
            $appelFond->getLibelle() ?? 'Appel de fonds',
            OperationType::APPEL_FONDS,
            (string) $appelFond->getNumero()
        );

        foreach ($appelFond->getLigneAppelFonds() as $ligne) {
            $coproprietaire = $ligne->getCoproprietaire();

            if (!$coproprietaire) {
                continue;
            }

            $compteCoproprietaire = $coproprietaire->getCompte();

            if (!$compteCoproprietaire) {
                throw new \LogicException(
                    sprintf(
                        'Le copropriétaire %s n\'a pas de compte',
                        $coproprietaire
                    )
                );
            }

            $soldeAvantAppel =
                $this->ecritureRepository
                ->calculerSoldeCompteADate(
                    $exercice,
                    $compteCoproprietaire,
                    $appelFond->getDateAppel()
                );

            $ligne->setSoldeAvantAppel(
                number_format(
                    $soldeAvantAppel,
                    2,
                    '.',
                    ''
                )
            );

            $this->comptabiliteService->creerDebit(
                $operation,
                $exercice,
                $compteCoproprietaire,
                $ligne->getMontant(),
                $coproprietaire
            );

            $this->comptabiliteService->creerCredit(
                $operation,
                $exercice,
                $compteProduit,
                $ligne->getMontant()
            );
        }

        $this->comptabiliteService->enregistrer();

        // Association de l'appel à son opération comptable
        $appelFond->setOperation($operation);

        $this->entityManager->flush();
    }
}
