<?php

namespace App\Controller;

use App\Repository\EcritureRepository;
use App\Repository\FactureFournisseurRepository;
use App\Repository\RepartitionRepository;
use App\Service\ContexteExerciceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RepartitionController extends AbstractController
{
    #[Route(
        '/repartitions/charges',
        name: 'app_repartition_charges',
        methods: ['GET']
    )]
    public function chargesParCoproprietaire(
        RepartitionRepository $repartitionRepository,
        EcritureRepository $ecritureRepository,
        FactureFournisseurRepository $factureFournisseurRepository,
        ContexteExerciceService $contexteExerciceService
    ): Response {
        $exercice = $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $repartitions = $repartitionRepository
            ->findPourExerciceAvecDetails($exercice);

        $groupes = [];
        $totalReparti = 0.0;
        $totalRepartiEau = 0.0;

        foreach ($repartitions as $repartition) {

            $coproprietaire = $repartition->getCoproprietaire();

            if ($coproprietaire === null) {
                continue;
            }

            $coproprietaireId = $coproprietaire->getId();

            if (!isset($groupes[$coproprietaireId])) {
                $groupes[$coproprietaireId] = [
                    'coproprietaire' => $coproprietaire,
                    'lignes' => [],
                    'total' => 0.0,
                ];
            }

            $montant = (float) $repartition->getMontant();

            $ecriture = $repartition->getEcriture();
            $compte = $ecriture?->getCompte();
            $operation = $ecriture?->getOperation();

            $estEau = $ecriture === null;

            if ($estEau) {
                $totalRepartiEau += $montant;
            }

            $groupes[$coproprietaireId]['lignes'][] = [
                'lot' => $repartition->getLot(),
                'compte' => $compte,
                'operation' => $operation,
                'nature' => $estEau
                    ? 'Eau'
                    : ($compte?->getLibelle() ?? 'Charge non identifiée'),
                'detail' => $estEau
                    ? 'Consommation individuelle et part commune'
                    : ($operation?->getLibelle() ?? ''),
                'montant' => $montant,
                'tantiemes' => $repartition->getTantiemes(),
                'estEau' => $estEau,
            ];

            $groupes[$coproprietaireId]['total'] += $montant;
            $totalReparti += $montant;
        }

        // ===========================
        // Contrôle détaillé
        // ===========================

        $controlesCharges = [];

        $ecrituresCharges = $ecritureRepository
            ->findChargesAvecRepartitions($exercice);

        $totalCharges = 0.0;

        foreach ($ecrituresCharges as $ecritureCharge) {

            $operation = $ecritureCharge->getOperation();
            $typeCharge = $operation?->getTypeCharge();

            /*
            * L'eau est traitée séparément.
            */
            if ($typeCharge?->isEau()) {
                continue;
            }

            $montantComptabilise =
                (float) $ecritureCharge->getDebit();

            $totalCharges += $montantComptabilise;

            $montantReparti = 0.0;

            foreach (
                $ecritureCharge->getRepartitions()
                as $repartition
            ) {
                $montantReparti +=
                    (float) $repartition->getMontant();
            }

            $montantReparti = round(
                $montantReparti,
                2
            );

            $ecartCharge = round(
                $montantComptabilise - $montantReparti,
                2
            );

            $controlesCharges[] = [
                'nature' => $typeCharge?->getNom()
                    ?? $ecritureCharge->getCompte()?->getLibelle()
                    ?? 'Charge non identifiée',
                'detail' => $operation?->getLibelle() ?? '',
                'montantComptabilise' => $montantComptabilise,
                'montantReparti' => $montantReparti,
                'ecart' => $ecartCharge,
                'equilibree' => abs($ecartCharge) < 0.01,
            ];
        }

        // ===========================
        // Contrôle Eau
        // ===========================

        $totalFacturesEau = (float)
        $factureFournisseurRepository
            ->calculerTotalFacturesEau($exercice);

        if (
            $totalFacturesEau > 0
            || $totalRepartiEau > 0
        ) {

            $ecartEau = round(
                $totalFacturesEau - $totalRepartiEau,
                2
            );

            $controlesCharges[] = [
                'nature' => 'Eau',
                'detail' => 'Ensemble des factures d’eau',
                'montantComptabilise' => $totalFacturesEau,
                'montantReparti' => round(
                    $totalRepartiEau,
                    2
                ),
                'ecart' => $ecartEau,
                'equilibree' => abs($ecartEau) < 0.01,
            ];

            $totalCharges += $totalFacturesEau;
        }

        // ===========================
        // Contrôle général
        // ===========================

        $totalCharges = round($totalCharges, 2);

        $ecart = round(
            $totalCharges - $totalReparti,
            2
        );

        $repartitionEquilibree =
            abs($ecart) < 0.01;

        $chargesPresentes = $totalCharges > 0;

        $eauPresente = false;
        $eauEquilibree = true;

        $toutesChargesEquilibrees = true;

        foreach ($controlesCharges as $controle) {
            if (!$controle['equilibree']) {
                $toutesChargesEquilibrees = false;
            }

            if ($controle['nature'] === 'Eau') {
                $eauPresente = true;
                $eauEquilibree = $controle['equilibree'];
            }
        }

        $validationGlobale =
            $chargesPresentes
            && $repartitionEquilibree
            && $toutesChargesEquilibrees;

        return $this->render(
            'repartition/charges_par_coproprietaire.html.twig',
            [
                'exercice' => $exercice,
                'groupes' => $groupes,
                'totalCharges' => $totalCharges,
                'totalReparti' => $totalReparti,
                'ecart' => $ecart,
                'repartitionEquilibree' => $repartitionEquilibree,
                'controlesCharges' => $controlesCharges,
                'chargesPresentes' => $chargesPresentes,
                'eauPresente' => $eauPresente,
                'eauEquilibree' => $eauEquilibree,
                'toutesChargesEquilibrees' => $toutesChargesEquilibrees,
                'validationGlobale' => $validationGlobale,
            ]
        );
    }
}
