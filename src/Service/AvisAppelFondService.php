<?php

namespace App\Service;

use App\Repository\AppelFondRepository;
use App\Repository\EcritureRepository;
use App\Repository\ExerciceRepository;

class AvisAppelFondService
{
    public function __construct(
        private AppelFondRepository $appelFondRepository,
        private EcritureRepository $ecritureRepository,
        private ExerciceRepository $exerciceRepository,
    ) {}

    /**
     * Prépare les avis individuels à partir des appels sélectionnés.
     *
     * @param int[] $appelIds
     */
    public function preparerAvis(array $appelIds): array
    {
        $appels = $this->appelFondRepository
            ->findPourAvisIndividuels($appelIds);

        if ($appels === []) {
            return [];
        }

        $dateReference = $appels[0]->getDateAppel();

        $exerciceReference = $this->exerciceRepository
            ->findPourDate($dateReference);

        if (!$exerciceReference) {
            throw new \LogicException(
                'Aucun exercice trouvé pour la date de référence.'
            );
        }

        foreach ($appels as $appel) {

            $exerciceAppel = $this->exerciceRepository
                ->findPourDate($appel->getDateAppel());

            if (
                !$exerciceAppel
                || $exerciceAppel->getId() !== $exerciceReference->getId()
            ) {
                throw new \LogicException(
                    'Les appels sélectionnés doivent appartenir au même exercice.'
                );
            }
        }

        $avis = [];

        foreach ($appels as $appel) {

            foreach ($appel->getLigneAppelFonds() as $ligne) {

                $coproprietaire = $ligne->getCoproprietaire();

                if (!$coproprietaire) {
                    throw new \LogicException(
                        'Une ligne d’appel de fonds ne possède pas de copropriétaire.'
                    );
                }

                $coproprietaireId = $coproprietaire->getId();

                $lot = $ligne->getLot();

                if (!$lot) {
                    throw new \LogicException(
                        'Une ligne d’appel de fonds ne possède pas de lot.'
                    );
                }

                $copropriete = $ligne->getLot()->getCopropriete();

                if (!isset($avis[$coproprietaireId])) {

                    $avis[$coproprietaireId] = [
                        'coproprietaire' => $coproprietaire,
                        'lignes' => [],
                        'totalCharges' => 0.0,
                        'totalTravaux' => 0.0,
                        'totalAppels' => 0.0,
                        'soldeAnterieur' => $this->calculerSoldeAnterieur(
                            $coproprietaire,
                            $dateReference
                        ),
                        'copropriete' => $copropriete,
                    ];
                }

                $montant = (float) $ligne->getMontant();

                $type = $appel->getBudget() !== null
                    ? 'charges'
                    : 'travaux';

                $detailsCharges = [];

                if ($type === 'charges') {
                    $detailsCharges = $this->detaillerCharges(
                        $appel->getBudget(),
                        $montant
                    );
                }

                $avis[$coproprietaireId]['lignes'][] = [
                    'appel' => $appel,
                    'lot' => $ligne->getLot(),
                    'montant' => $montant,
                    'type' => $type,
                    'detailsCharges' => $detailsCharges,
                ];

                if ($type === 'charges') {
                    $avis[$coproprietaireId]['totalCharges'] += $montant;
                } else {
                    $avis[$coproprietaireId]['totalTravaux'] += $montant;
                }

                $avis[$coproprietaireId]['totalAppels'] += $montant;
            }
        }

        foreach ($avis as &$donnees) {

            $donnees['totalCharges'] = round(
                $donnees['totalCharges'],
                2
            );

            $donnees['totalTravaux'] = round(
                $donnees['totalTravaux'],
                2
            );

            $donnees['totalAppels'] = round(
                $donnees['totalAppels'],
                2
            );

            $donnees['totalARegler'] = round(
                $donnees['totalAppels']
                    + $donnees['soldeAnterieur'],
                2
            );
        }

        unset($donnees);

        return $avis;
    }

    /**
     * Répartit le montant d'une ligne d'appel
     * entre les différents postes du budget.
     */
    private function detaillerCharges(
        \App\Entity\Budget $budget,
        float $montantAppel
    ): array {
        $totalBudget = $budget->getTotal();

        if ($totalBudget <= 0) {
            throw new \LogicException(
                'Le montant total du budget est invalide.'
            );
        }

        $details = [];
        $totalReparti = 0.0;

        $lignesBudget = $budget->getLignes()->toArray();
        $dernierIndex = count($lignesBudget) - 1;

        foreach ($lignesBudget as $index => $ligneBudget) {

            if ($index === $dernierIndex) {
                $montant = round(
                    $montantAppel - $totalReparti,
                    2
                );
            } else {
                $montant = round(
                    $montantAppel
                        * (float) $ligneBudget->getMontant()
                        / $totalBudget,
                    2
                );

                $totalReparti += $montant;
            }

            $details[] = [
                'typeCharge' => $ligneBudget->getTypeCharge(),
                'budgetAnnuel' => (float) $ligneBudget->getMontant(),
                'montant' => $montant,
            ];
        }

        return $details;
    }

    private function calculerSoldeAnterieur(
        \App\Entity\Coproprietaire $coproprietaire,
        \DateTimeInterface $dateReference
    ): float {
        $compte = $coproprietaire->getCompte();

        if (!$compte) {
            throw new \LogicException(
                sprintf(
                    'Le copropriétaire %s ne possède pas de compte comptable.',
                    $coproprietaire->getNomComplet()
                )
            );
        }

        $exercice = $this->exerciceRepository
            ->findPourDate($dateReference);

        if (!$exercice) {
            throw new \LogicException(
                'Aucun exercice trouvé pour la date de référence.'
            );
        }

        $dateVeille = \DateTimeImmutable::createFromInterface(
            $dateReference
        )->setTime(0, 0)->modify('-1 second');

        return round(
            $this->ecritureRepository->calculerSoldeCompteADate(
                $exercice,
                $compte,
                $dateVeille
            ),
            2
        );
    }
}