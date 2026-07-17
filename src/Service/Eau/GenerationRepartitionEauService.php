<?php

namespace App\Service\Eau;

use App\Entity\Exercice;
use App\Entity\Repartition;
use App\Repository\RepartitionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GenerationRepartitionEauService
{
    public function __construct(
        private readonly EauService $eauService,
        private readonly EntityManagerInterface $entityManager,
        private readonly RepartitionRepository $repartitionRepository,
    ) {}

    public function generer(
        Exercice $exercice
    ): void {

        // ===========================
        // Contrôle
        // ===========================       

        $repartitions = $this->repartitionRepository
            ->findBy([
                'exercice' => $exercice,
                'ecriture' => null,
            ]);

        if ($repartitions !== []) {
            throw new \DomainException(
                'La répartition de l’eau a déjà été générée pour cet exercice.'
            );
        }

        // ===========================
        // Calcul
        // ===========================

        $calcul = $this->eauService->calculer(
            $exercice
        );

        foreach (
            $calcul->getLots()
            as $calculLot
        ) {
            $lot = $calculLot->getLot();

            $coproprietaire =
                $lot->getCoproprietaireActuel(
                    $exercice->getDateFin()
                );

            if ($coproprietaire === null) {
                throw new \DomainException(
                    sprintf(
                        'Aucun copropriétaire n’est défini pour le lot %s.',
                        $lot->getReference()
                    )
                );
            }

            $repartition = new Repartition();

            $repartition
                ->setExercice($exercice)
                ->setLot($lot)
                ->setCoproprietaire(
                    $coproprietaire
                )
                ->setEcriture(null)
                ->setTantiemes(
                    $lot->getTantiemes()
                )
                ->setMontant(
                    number_format(
                        $calculLot->getMontantTotal(),
                        2,
                        '.',
                        ''
                    )
                );

            $this->entityManager->persist(
                $repartition
            );
        }

        $this->entityManager->flush();
    }
}
