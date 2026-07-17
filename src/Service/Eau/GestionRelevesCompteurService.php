<?php

namespace App\Service\Eau;

use App\Entity\Exercice;
use App\Entity\ReleveCompteur;
use App\Repository\CompteurEauRepository;
use App\Repository\ReleveCompteurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GestionRelevesCompteurService
{
    public function __construct(
        private readonly CompteurEauRepository $compteurEauRepository,
        private readonly ReleveCompteurRepository $releveCompteurRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function preparerAffichage(
        Exercice $exercice
    ): array {
        $compteurs = $this->compteurEauRepository
            ->findTousActifs();

        $lignes = [];
        $dateReleve = new \DateTimeImmutable();

        foreach ($compteurs as $compteur) {
            $releveCourant = $this->releveCompteurRepository
                ->findByCompteurAndExercice(
                    $compteur,
                    $exercice
                );

            $relevePrecedent = $this->releveCompteurRepository
                ->findDernierAvantExercice(
                    $compteur,
                    $exercice
                );

            $ancienIndex = $relevePrecedent
                ? $relevePrecedent->getValeurIndex()
                : $compteur->getIndexInitial();

            if ($releveCourant !== null) {
                $dateReleve = $releveCourant->getDateReleve()
                    ?? $dateReleve;
            }

            $lignes[] = [
                'compteur' => $compteur,
                'ancienIndex' => $ancienIndex,
                'releveCourant' => $releveCourant,
            ];
        }

        return [
            'lignes' => $lignes,
            'dateReleve' => $dateReleve,
        ];
    }

    public function enregistrer(
        Exercice $exercice,
        string $dateReleveBrute,
        array $valeurs
    ): int {
        $dateReleve = $this->creerDateReleve(
            $dateReleveBrute
        );

        $this->verifierDateDansExercice(
            $dateReleve,
            $exercice
        );

        $compteurs = $this->compteurEauRepository
            ->findTousActifs();

        $nombreReleves = 0;

        foreach ($compteurs as $compteur) {
            $compteurId = $compteur->getId();

            if ($compteurId === null) {
                continue;
            }

            $valeurBrute = $valeurs[(string) $compteurId]
                ?? null;

            if (
                $valeurBrute === null
                || trim((string) $valeurBrute) === ''
            ) {
                continue;
            }

            $valeurIndex = $this->validerIndex(
                $valeurBrute,
                $compteur->getReference()
            );

            $relevePrecedent = $this->releveCompteurRepository
                ->findDernierAvantExercice(
                    $compteur,
                    $exercice
                );

            $ancienIndex = $relevePrecedent
                ? $relevePrecedent->getValeurIndex()
                : $compteur->getIndexInitial();

            if (
                $ancienIndex !== null
                && $valeurIndex < $ancienIndex
            ) {
                throw new \DomainException(
                    sprintf(
                        'L’index du compteur %s ne peut pas être inférieur à %d.',
                        $compteur->getReference(),
                        $ancienIndex
                    )
                );
            }

            $releve = $this->releveCompteurRepository
                ->findByCompteurAndExercice(
                    $compteur,
                    $exercice
                );

            if ($releve === null) {
                $releve = new ReleveCompteur();

                $releve
                    ->setCompteur($compteur)
                    ->setExercice($exercice);

                $this->entityManager->persist($releve);
            }

            $releve
                ->setDateReleve($dateReleve)
                ->setValeurIndex($valeurIndex);

            $nombreReleves++;
        }

        if ($nombreReleves === 0) {
            throw new \DomainException(
                'Aucun index n’a été renseigné.'
            );
        }

        $this->entityManager->flush();

        return $nombreReleves;
    }

    private function creerDateReleve(
        string $dateReleveBrute
    ): \DateTimeImmutable {
        $dateReleve = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dateReleveBrute
        );

        $erreurs = \DateTimeImmutable::getLastErrors();

        if (
            $dateReleve === false
            || (
                is_array($erreurs)
                && (
                    $erreurs['warning_count'] > 0
                    || $erreurs['error_count'] > 0
                )
            )
        ) {
            throw new \DomainException(
                'La date du relevé est invalide.'
            );
        }

        return $dateReleve;
    }

    private function verifierDateDansExercice(
        \DateTimeImmutable $dateReleve,
        Exercice $exercice
    ): void {
        if (
            $dateReleve < $exercice->getDateDebut()
            || $dateReleve > $exercice->getDateFin()
        ) {
            throw new \DomainException(
                sprintf(
                    'La date du relevé doit être comprise entre le %s et le %s.',
                    $exercice->getDateDebut()->format('d/m/Y'),
                    $exercice->getDateFin()->format('d/m/Y')
                )
            );
        }
    }

    private function validerIndex(
        mixed $valeurBrute,
        string $reference
    ): int {
        if (
            filter_var(
                $valeurBrute,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new \DomainException(
                sprintf(
                    'L’index du compteur %s est invalide.',
                    $reference
                )
            );
        }

        $valeurIndex = (int) $valeurBrute;

        if ($valeurIndex < 0) {
            throw new \DomainException(
                sprintf(
                    'L’index du compteur %s ne peut pas être négatif.',
                    $reference
                )
            );
        }

        return $valeurIndex;
    }
}
