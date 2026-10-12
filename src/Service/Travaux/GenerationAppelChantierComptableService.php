<?php

namespace App\Service\Travaux;

use App\Entity\AppelFond;
use App\Enum\OperationType;
use App\Repository\EcritureRepository;
use App\Repository\ExerciceRepository;
use App\Service\ComptabiliteService;
use Doctrine\ORM\EntityManagerInterface;

class GenerationAppelChantierComptableService
{
    public function __construct(
        private ComptabiliteService $comptabiliteService,
        private ExerciceRepository $exerciceRepository,
        private EcritureRepository $ecritureRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function generer(AppelFond $appelFond): void
    {
        $chantier = $appelFond->getChantier();

        if (!$chantier) {
            throw new \LogicException(
                'Cet appel de fonds n\'est rattaché à aucun chantier.'
            );
        }

        $compteProvision = $chantier->getCompteProvision();

        if (!$compteProvision) {
            throw new \LogicException(
                'Le compte de provisions du chantier est introuvable.'
            );
        }

        // L'exercice est déterminé par la date de l'appel.
        $exercice = $this->exerciceRepository->findPourDate(
            $appelFond->getDateAppel()
        );

        if (!$exercice) {
            throw new \LogicException(
                sprintf(
                    'Aucun exercice ne correspond à la date d\'appel du %s.',
                    $appelFond->getDateAppel()->format('d/m/Y')
                )
            );
        }

        $operation = $this->comptabiliteService->creerOperation(
            $appelFond->getDateAppel(),
            $appelFond->getLibelle() ?? 'Appel de fonds travaux',
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
                        'Le copropriétaire %s n\'a pas de compte.',
                        $coproprietaire
                    )
                );
            }

            $soldeAvantAppel =
                $this->ecritureRepository->calculerSoldeCompteADate(
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

            // Débit du compte individuel du copropriétaire.
            $this->comptabiliteService->creerDebit(
                $operation,
                $exercice,
                $compteCoproprietaire,
                $ligne->getMontant(),
                $coproprietaire
            );

            // Crédit du compte de provisions propre au chantier.
            $this->comptabiliteService->creerCredit(
                $operation,
                $exercice,
                $compteProvision,
                $ligne->getMontant()
            );
        }

        $this->comptabiliteService->enregistrer();

        $appelFond->setOperation($operation);

        $this->entityManager->flush();
    }
}
