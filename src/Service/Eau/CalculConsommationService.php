<?php

namespace App\Service\Eau;

use App\Dto\Eau\ConsommationCompteur;
use App\Dto\Eau\ConsommationImmeuble;
use App\Entity\Exercice;
use App\Repository\CompteurEauRepository;
use App\Repository\ReleveCompteurRepository;

final class CalculConsommationService
{
    public function __construct(
        private readonly CompteurEauRepository $compteurEauRepository,
        private readonly ReleveCompteurRepository $releveCompteurRepository,
    ) {}

    public function calculer(
        Exercice $exercice
    ): ConsommationImmeuble {
        $consommations = [];

        $compteurs = $this->compteurEauRepository
            ->findTousActifs();

        foreach ($compteurs as $compteur) {
            $relevePrecedent = $this->releveCompteurRepository
                ->findDernierAvantExercice(
                    $compteur,
                    $exercice
                );

            $releveCourant = $this->releveCompteurRepository
                ->findByCompteurAndExercice(
                    $compteur,
                    $exercice
                );

            $ancienIndex = $relevePrecedent
                ? $relevePrecedent->getValeurIndex()
                : $compteur->getIndexInitial();

            $nouvelIndex = $releveCourant
                ? $releveCourant->getValeurIndex()
                : null;

            $consommation = null;

            if (
                $ancienIndex !== null
                && $nouvelIndex !== null
                && $nouvelIndex >= $ancienIndex
            ) {
                $consommation =
                    $nouvelIndex - $ancienIndex;
            }

            $consommations[] =
                new ConsommationCompteur(
                    compteur: $compteur,
                    ancienIndex: $ancienIndex,
                    nouvelIndex: $nouvelIndex,
                    consommation: $consommation,
                );
        }

        return new ConsommationImmeuble(
            $consommations
        );
    }
}
