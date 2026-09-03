<?php

namespace App\Controller;

use App\Repository\EcritureRepository;
use App\Repository\FactureFournisseurRepository;
use App\Repository\RepartitionRepository;
use App\Service\ContexteExerciceService;
use App\Service\EtatRepartitionChargesService;
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
        ContexteExerciceService $contexteExerciceService,
        EtatRepartitionChargesService $etatRepartitionChargesService,
    ): Response {
        $exercice =
            $contexteExerciceService->getExercice();

        if (!$exercice) {
            throw $this->createNotFoundException(
                'Aucun exercice sélectionné.'
            );
        }

        $etat = $etatRepartitionChargesService
            ->construire($exercice);

        return $this->render(
            'repartition/charges_par_coproprietaire.html.twig',
            [
                'exercice' => $exercice,
                ...$etat,
            ]
        );
    }

    #[Route(
        '/repartitions/charges/synthese',
        name: 'app_repartition_charges_synthese',
        methods: ['GET']
    )]
    public function chargesSynthese(
        RepartitionRepository $repartitionRepository,
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
        $postes = [];

        foreach ($repartitions as $repartition) {

            $coproprietaire =
                $repartition->getCoproprietaire();

            if ($coproprietaire === null) {
                continue;
            }

            $coproprietaireId =
                $coproprietaire->getId();

            $montant =
                (float) $repartition->getMontant();

            $ecriture =
                $repartition->getEcriture();

            $operation =
                $ecriture?->getOperation();

            $compte =
                $ecriture?->getCompte();

            $typeCharge =
                $operation?->getTypeCharge();

            /*
         * Les répartitions d'eau n'ont pas
         * d'écriture associée dans ton modèle.
         */
            if ($ecriture === null) {

                $clePoste = 'eau';
                $nomPoste = 'Eau';
            } elseif ($typeCharge !== null) {

                $clePoste =
                    'type_' . $typeCharge->getId();

                $nomPoste =
                    $typeCharge->getNom();
            } else {

                /*
             * Sécurité pour les anciennes données
             * ou une charge sans TypeCharge.
             */
                $clePoste =
                    'compte_' . ($compte?->getId() ?? 'inconnu');

                $nomPoste =
                    $compte?->getLibelle()
                    ?? 'Charge non identifiée';
            }

            /*
         * Liste globale des postes :
         * elle servira à construire les colonnes.
         */
            $postes[$clePoste] = $nomPoste;

            /*
         * Initialisation du copropriétaire.
         */
            if (!isset($groupes[$coproprietaireId])) {
                $groupes[$coproprietaireId] = [
                    'coproprietaire' => $coproprietaire,
                    'postes' => [],
                    'total' => 0.0,
                ];
            }

            /*
         * Initialisation du poste.
         */
            if (
                !isset(
                    $groupes[$coproprietaireId]['postes'][$clePoste]
                )
            ) {
                $groupes[$coproprietaireId]['postes'][$clePoste] = 0.0;
            }

            /*
         * Addition des répartitions du même poste.
         */
            $groupes[$coproprietaireId]['postes'][$clePoste]
                += $montant;

            $groupes[$coproprietaireId]['total']
                += $montant;
        }

        /*
     * Arrondis.
     */
        foreach ($groupes as &$groupe) {

            foreach (
                $groupe['postes']
                as $clePoste => $montant
            ) {
                $groupe['postes'][$clePoste] =
                    round($montant, 2);
            }

            $groupe['total'] =
                round(
                    $groupe['total'],
                    2
                );
        }

        unset($groupe);

        /*
     * Tri alphabétique des postes.
     */
        asort(
            $postes,
            SORT_NATURAL | SORT_FLAG_CASE
        );

        /*
     * Total général par poste.
     */
        $totauxPostes = [];

        foreach ($postes as $clePoste => $nomPoste) {
            $totauxPostes[$clePoste] = 0.0;
        }

        $totalGeneral = 0.0;

        foreach ($groupes as $groupe) {

            foreach (
                $groupe['postes']
                as $clePoste => $montant
            ) {
                $totauxPostes[$clePoste]
                    += $montant;
            }

            $totalGeneral +=
                $groupe['total'];
        }

        foreach (
            $totauxPostes
            as $clePoste => $montant
        ) {
            $totauxPostes[$clePoste] =
                round($montant, 2);
        }

        $totalGeneral =
            round(
                $totalGeneral,
                2
            );

        return $this->render(
            'repartition/charges_synthese.html.twig',
            [
                'exercice' => $exercice,
                'groupes' => $groupes,
                'postes' => $postes,
                'totauxPostes' => $totauxPostes,
                'totalGeneral' => $totalGeneral,
            ]
        );
    }
}
