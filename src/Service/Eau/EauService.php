<?php

namespace App\Service\Eau;

use App\Entity\Exercice;
use App\Repository\FactureFournisseurRepository;
use App\Dto\Eau\CalculEau;
use App\Dto\Eau\CalculEauLot;
use App\Dto\Eau\ConsommationImmeuble;

final class EauService
{
    public function __construct(
        private readonly CalculConsommationService $calculConsommationService,
        private readonly FactureFournisseurRepository $factureRepository,
    ) {}

    // ===========================
    // Chargement des données
    // ===========================

    private function chargerMontantFactures(
        Exercice $exercice
    ): float {
        $montant = (float) $this->factureRepository
            ->calculerTotalFacturesEau($exercice);

        if ($montant <= 0) {
            throw new \DomainException(
                'Aucune facture d’eau comptabilisée n’a été trouvée pour cet exercice.'
            );
        }

        return $montant;
    }

    private function chargerConsommations(
        Exercice $exercice
    ): ConsommationImmeuble {
        $bilan = $this->calculConsommationService
            ->calculer($exercice);

        if (!$bilan->isComplete()) {
            throw new \DomainException(
                'Tous les relevés de compteurs doivent être renseignés avant de calculer la répartition de l’eau.'
            );
        }

        if ($bilan->hasEcartNegatif()) {
            throw new \DomainException(
                'La consommation des compteurs individuels dépasse celle du compteur général.'
            );
        }

        return $bilan;
    }

    // ===========================
    // Calculs
    // ===========================

    public function calculer(
        Exercice $exercice
    ): CalculEau {
        $consommations = $this->chargerConsommations(
            $exercice
        );

        $montantFactures = $this->chargerMontantFactures(
            $exercice
        );

        $prixM3 = $this->calculerPrixM3(
            $montantFactures,
            $consommations
        );

        $lots = $this->calculerLots(
            $consommations,
            $prixM3,
            $exercice
        );

        $consommationGenerale =
            $consommations->getConsommationGenerale();

        $consommationCommune =
            $consommations->getConsommationCommuns();

        if (
            $consommationGenerale === null
            || $consommationCommune === null
        ) {
            throw new \DomainException(
                'Les consommations d’eau sont incomplètes.'
            );
        }

        return new CalculEau(
            montantTotalFactures: $montantFactures,
            prixM3: $prixM3,
            consommationGenerale: $consommationGenerale,
            consommationLots: $consommations->getConsommationLots(),
            consommationCommune: $consommationCommune,
            lots: $lots,
        );
    }
    
    
    private function calculerPartCommune(
        float $coutCommuns,
        int $tantiemesLot,
        int $totalTantiemes
    ): float {
        return round(
            $coutCommuns
                * $tantiemesLot
                / $totalTantiemes,
            2
        );
    }

    private function calculerPartIndividuelle(
        int $consommation,
        float $prixM3
    ): float {
        return round(
            $consommation * $prixM3,
            2
        );
    }

    private function calculerTotalTantiemes(
        ConsommationImmeuble $consommations
    ): int {
        $total = 0;

        foreach (
            $consommations->getCompteursIndividuels()
            as $consommationCompteur
        ) {
            $lot = $consommationCompteur->getLot();

            if ($lot === null) {
                throw new \DomainException(
                    sprintf(
                        'Le compteur %s n’est associé à aucun lot.',
                        $consommationCompteur->getReference()
                    )
                );
            }

            $tantiemes = $lot->getTantiemes();

            if ($tantiemes === null || $tantiemes <= 0) {
                throw new \DomainException(
                    sprintf(
                        'Les tantièmes du lot %s sont absents ou invalides.',
                        $lot->getReference()
                    )
                );
            }

            $total += $tantiemes;
        }

        if ($total <= 0) {
            throw new \DomainException(
                'Le total des tantièmes généraux est invalide.'
            );
        }

        return $total;
    }

    private function calculerPrixM3(
        float $montantFactures,
        ConsommationImmeuble $consommations
    ): float {
        $consommationGenerale =
            $consommations->getConsommationGenerale();

        if (
            $consommationGenerale === null
            || $consommationGenerale <= 0
        ) {
            throw new \DomainException(
                'La consommation du compteur général doit être supérieure à zéro.'
            );
        }

        return $montantFactures
            / $consommationGenerale;
    }

    /**
     * @return CalculEauLot[]
     */
    private function calculerLots(
        ConsommationImmeuble $consommations,
        float $prixM3,
        Exercice $exercice
    ): array {
        $totalTantiemes = $this->calculerTotalTantiemes(
            $consommations
        );

        $consommationCommune =
            $consommations->getConsommationCommuns();

        if ($consommationCommune === null) {
            throw new \DomainException(
                'La consommation des parties communes ne peut pas être calculée.'
            );
        }

        $coutCommuns =
            $consommationCommune * $prixM3;

        $lots = [];

        foreach (
            $consommations->getCompteursIndividuels()
            as $consommationCompteur
        ) {
            $lot = $consommationCompteur->getLot();

            if ($lot === null) {
                throw new \DomainException(
                    sprintf(
                        'Le compteur %s n’est associé à aucun lot.',
                        $consommationCompteur->getReference()
                    )
                );
            }

            $consommation =
                $consommationCompteur->getConsommation();

            if ($consommation === null) {
                throw new \DomainException(
                    sprintf(
                        'La consommation du compteur %s est absente.',
                        $consommationCompteur->getReference()
                    )
                );
            }

            $tantiemes = $lot->getTantiemes();

            if (
                $tantiemes === null
                || $tantiemes <= 0
            ) {
                throw new \DomainException(
                    sprintf(
                        'Les tantièmes du lot %s sont invalides.',
                        $lot->getReference()
                    )
                );
            }

            $coproprietaire = $lot->getCoproprietaireActuel(
                $exercice->getDateFin()
            );

            if ($coproprietaire === null) {
                throw new \DomainException(
                    sprintf(
                        'Aucun copropriétaire n’est défini pour le lot %s au %s.',
                        $lot->getReference(),
                        $exercice->getDateFin()->format('d/m/Y')
                    )
                );
            }

            $partIndividuelle =
                $this->calculerPartIndividuelle(
                    $consommation,
                    $prixM3
                );

            $partCommune =
                $this->calculerPartCommune(
                    $coutCommuns,
                    $tantiemes,
                    $totalTantiemes
                );

            $lots[] = new CalculEauLot(
                lot: $lot,
                coproprietaire: $coproprietaire,
                consommation: $consommation,
                partIndividuelle: $partIndividuelle,
                partCommune: $partCommune,
                montantTotal: round(
                    $partIndividuelle + $partCommune,
                    2
                ),
            );
        }

        return $lots;
    }

    public function genererRepartition(
        Exercice $exercice
    ): void {
        // plus tard
    }
}
