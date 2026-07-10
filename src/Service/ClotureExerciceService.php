<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Coproprietaire;
use App\Entity\Operation;
use App\Entity\Ecriture;
use App\Dto\Cloture\SoldeReportable;
use App\Dto\Cloture\EtatCloture;
use App\Dto\Cloture\ResultatExercice;
use App\Dto\Cloture\ClotureComptesGestion;
use App\Dto\Cloture\ClotureCompteGestionLigne;
use App\Dto\Cloture\ANouveaux;
use App\Enum\OperationType;
use App\Enum\OperationStatut;
use App\Enum\ExerciceStatut;
use App\Enum\CompteType;
use App\Service\ComptabiliteService;
use App\Repository\ExerciceRepository;
use App\Repository\CompteRepository;
use App\Repository\EcritureRepository;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClotureExerciceService
{
    private EntityManagerInterface $em;
    private ExerciceRepository $exerciceRepository;
    private CompteRepository $compteRepository;
    private EcritureRepository $ecritureRepository;
    private OperationRepository $operationRepository;
    private ComptabiliteService $comptabiliteService;

    public function __construct(
        ExerciceRepository $exerciceRepository,
        CompteRepository $compteRepository,
        EntityManagerInterface $em,
        EcritureRepository $ecritureRepository,
        OperationRepository $operationRepository,
        ComptabiliteService $comptabiliteService,   
    ) {
        $this->compteRepository = $compteRepository;
        $this->exerciceRepository = $exerciceRepository;
        $this->ecritureRepository = $ecritureRepository;
        $this->operationRepository = $operationRepository;
        $this->em = $em;
        $this->comptabiliteService = $comptabiliteService;
    }

    /**
     * @return SoldeReportable[]
     */
    public function calculerSoldesReportables(
        Exercice $exercice
    ): array {
        $resultats =
            $this->ecritureRepository
            ->calculerSoldesReportables($exercice);

        $soldes = [];

        foreach ($resultats as $ligne) {
            $debit = (float) $ligne['debit'];
            $credit = (float) $ligne['credit'];

            $solde = $debit - $credit;

            if (abs($solde) < 0.01) {
                continue;
            }

            $compte = $this->compteRepository->find($ligne['compteId']);

            if (!$compte) {
                continue;
            }

            $coproprietaire = null;

            if ($ligne['coproprietaireId'] !== null) {
                $coproprietaire =
                    $this->em
                    ->getRepository(Coproprietaire::class)
                    ->find($ligne['coproprietaireId']);
            }

            $soldes[] = new SoldeReportable(
                compte: $compte,
                coproprietaire: $coproprietaire,
                debit: max($solde, 0),
                credit: max(-$solde, 0),
            );
        }

        return $soldes;
    }

    public function getEtatCloture(
        Exercice $exercice
    ): EtatCloture {
        $erreurs = [];

        $budgetsVerrouilles =
            $this->budgetsSontVerrouilles(
                $exercice,
                $erreurs
            );

        $operationsEquilibrees =
            $this->operationsSontEquilibrees(
                $exercice,
                $erreurs
            );

        $clotureComptesGestionGeneree =
            $this->operationRepository
            ->clotureComptesGestionExiste($exercice);

        $exerciceSuivant =
            $this->exerciceRepository
            ->findSuivant($exercice);

        $anouveauxGeneres = false;

        if ($exerciceSuivant) {
            $anouveauxGeneres =
                $this->operationRepository
                ->aNouveauxExistent($exerciceSuivant);
        }

        if (!$exercice->estTermine()) {
            $erreurs[] = sprintf(
                'L’exercice ne peut pas être clôturé avant sa date de fin : %s.',
                $exercice->getDateFin()->format('d/m/Y')
            );
        }

        return new EtatCloture(
            exerciceActif: $exercice->isActif(),
            exerciceCloture: $exercice->isCloture(),
            exerciceTermine: $exercice->estTermine(),
            budgetsVerrouilles: $budgetsVerrouilles,
            operationsEquilibrees: $operationsEquilibrees,
            regularisationsGenerees: $exercice->isRegularisationsGenerees(),
            anouveauxGeneres: $anouveauxGeneres,
            clotureComptesGestionGeneree: $clotureComptesGestionGeneree,
            exerciceSuivantExiste: $exerciceSuivant !== null,
            exerciceSuivant: $exerciceSuivant,
            erreurs: $erreurs,
        );
    }

    public function calculerResultatExercice(
        Exercice $exercice
    ): ResultatExercice {
        $lignes =
            $this->ecritureRepository
            ->calculerTotauxChargesProduits($exercice);

        $charges = 0.0;
        $produits = 0.0;

        foreach ($lignes as $ligne) {
            $debit = (float) $ligne['debit'];
            $credit = (float) $ligne['credit'];

            if ($ligne['type'] === CompteType::CHARGE) {
                $charges += $debit - $credit;
            }

            if ($ligne['type'] === CompteType::PRODUIT) {
                $produits += $credit - $debit;
            }
        }

        return new ResultatExercice(
            charges: $charges,
            produits: $produits,
            resultat: $produits - $charges,
        );
    }

    public function calculerClotureComptesGestion(
        Exercice $exercice
    ): ClotureComptesGestion {
        $lignesBrutes =
            $this->ecritureRepository
            ->calculerSoldesComptesGestion($exercice);

        $compteResultat =
            $this->compteRepository->findByNumeroOrFail('120000');

        $lignes = [];

        $charges = 0.0;
        $produits = 0.0;

        foreach ($lignesBrutes as $ligne) {
            $compte =
                $this->compteRepository->find($ligne['compteId']);

            if (!$compte) {
                continue;
            }

            $debit = (float) $ligne['debit'];
            $credit = (float) $ligne['credit'];

            $solde = $debit - $credit;

            if (abs($solde) < 0.01) {
                continue;
            }

            if ($compte->getType() === CompteType::CHARGE) {
                $charges += $solde;

                $lignes[] = new ClotureCompteGestionLigne(
                    compte: $compte,
                    debit: 0,
                    credit: $solde,
                );
            }

            if ($compte->getType() === CompteType::PRODUIT) {
                $produit = -$solde;
                $produits += $produit;

                $lignes[] = new ClotureCompteGestionLigne(
                    compte: $compte,
                    debit: $produit,
                    credit: 0,
                );
            }
        }

        $resultat = $produits - $charges;

        if ($resultat > 0.01) {
            $lignes[] = new ClotureCompteGestionLigne(
                compte: $compteResultat,
                debit: 0,
                credit: $resultat,
            );
        } elseif ($resultat < -0.01) {
            $lignes[] = new ClotureCompteGestionLigne(
                compte: $compteResultat,
                debit: abs($resultat),
                credit: 0,
            );
        }

        return new ClotureComptesGestion(
            lignes: $lignes,
            charges: $charges,
            produits: $produits,
            resultat: $resultat,
        );
    }

    public function genererClotureComptesGestion(
        Exercice $exercice
    ): void {

        if (
            $this->operationRepository
            ->clotureComptesGestionExiste($exercice)
        ) {
            throw new \LogicException(
                'Les écritures de clôture des comptes de gestion ont déjà été générées.'
            );
        }

        $cloture =
            $this->calculerClotureComptesGestion($exercice);

        if (empty($cloture->lignes)) {
            return;
        }

        $operation =
            $this->comptabiliteService->creerOperation(
                $exercice->getDateFin(),
                sprintf(
                    'Clôture des comptes de gestion - %s',
                    $exercice->getNom()
                ),
                OperationType::CLOTURE
            );

        foreach ($cloture->lignes as $ligne) {

            if ((float) $ligne->debit > 0) {
                $this->comptabiliteService->creerDebit(
                    $operation,
                    $exercice,
                    $ligne->compte,
                    $ligne->debit
                );
            }

            if ((float) $ligne->credit > 0) {
                $this->comptabiliteService->creerCredit(
                    $operation,
                    $exercice,
                    $ligne->compte,
                    $ligne->credit
                );
            }
        }

        $this->comptabiliteService->enregistrer($operation);

        $this->em->flush();
    }

    public function getOperationClotureComptesGestion(
        Exercice $exercice
    ): ?Operation {
        return $this->operationRepository
            ->findClotureComptesGestion($exercice);
    }

    public function getClotureComptesGestion(
        Exercice $exercice
    ): ClotureComptesGestion {
        $operation =
            $this->getOperationClotureComptesGestion($exercice);

        if (!$operation) {
            return $this->calculerClotureComptesGestion($exercice);
        }

        $lignes = [];

        $charges = 0.0;
        $produits = 0.0;

        foreach ($operation->getEcritures() as $ecriture) {
            $debit = (float) $ecriture->getDebit();
            $credit = (float) $ecriture->getCredit();

            $lignes[] = new ClotureCompteGestionLigne(
                compte: $ecriture->getCompte(),
                debit: $debit,
                credit: $credit,
            );

            if ($ecriture->getCompte()->getType() === CompteType::CHARGE) {
                $charges += $credit - $debit;
            }

            if ($ecriture->getCompte()->getType() === CompteType::PRODUIT) {
                $produits += $debit - $credit;
            }
        }

        return new ClotureComptesGestion(
            lignes: $lignes,
            charges: $charges,
            produits: $produits,
            resultat: $produits - $charges,
            generee: true,
            operation: $operation,
        );
    }

    public function calculerANouveaux(
        Exercice $exercice
    ): ANouveaux {
        $soldes = $this->calculerSoldesReportables($exercice);

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($soldes as $solde) {
            $totalDebit += $solde->debit;
            $totalCredit += $solde->credit;
        }

        return new ANouveaux(
            lignes: $soldes,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
        );
    }

    public function getANouveaux(
        Exercice $exercice
    ): ANouveaux {
        $exerciceSuivant =
            $this->exerciceRepository->findSuivant($exercice);

        if (!$exerciceSuivant) {
            return $this->calculerANouveaux($exercice);
        }

        $operation =
            $this->operationRepository->findANouveaux($exerciceSuivant);

        if (!$operation) {
            return $this->calculerANouveaux($exercice);
        }

        $lignes = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($operation->getEcritures() as $ecriture) {
            $debit = (float) $ecriture->getDebit();
            $credit = (float) $ecriture->getCredit();

            $lignes[] = new SoldeReportable(
                compte: $ecriture->getCompte(),
                coproprietaire: $ecriture->getCoproprietaire(),
                debit: $debit,
                credit: $credit,
            );

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return new ANouveaux(
            lignes: $lignes,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            generes: true,
            operation: $operation,
        );
    }

    public function cloturerExercice(
        Exercice $exercice
    ): void {
        $etat = $this->getEtatCloture($exercice);

        if (!$etat->peutCloturer()) {
            throw new \LogicException(
                'L’exercice ne peut pas être clôturé.'
            );
        }

        $exerciceSuivant = $etat->exerciceSuivant;

        if (!$exerciceSuivant) {
            throw new \LogicException(
                'Aucun exercice suivant.'
            );
        }

        $this->em->wrapInTransaction(
            function () use (
                $exercice,
                $exerciceSuivant
            ): void {
                $exercice
                    ->setActif(false)
                    ->setCloture(true)
                    ->setStatut(ExerciceStatut::CLOTURE);

                $exerciceSuivant
                    ->setActif(true);

                $this->em->flush();
            }
        );
    }

    private function budgetsSontVerrouilles(
        Exercice $exercice,
        array &$erreurs
    ): bool {
        $ok = true;

        foreach ($exercice->getBudgets() as $budget) {
            if (!$budget->isVerrouille()) {
                $ok = false;

                $erreurs[] = sprintf(
                    'Le budget "%s" n’est pas verrouillé.',
                    $budget->getLibelle()
                );
            }
        }

        return $ok;
    }

    private function operationsSontEquilibrees(
        Exercice $exercice,
        array &$erreurs
    ): bool {
        try {
            $this->verifierEquilibreExercice($exercice);

            return true;
        } catch (\Throwable $e) {
            $erreurs[] = $e->getMessage();

            return false;
        }
    }

    private function verifierEquilibreExercice(
        Exercice $exercice
    ): void {
        $totaux = $this->ecritureRepository->calculerTotauxParExercice($exercice);

        $debit = (float) $totaux['debit'];
        $credit = (float) $totaux['credit'];

        if (abs($debit - $credit) > 0.01) {
            throw new \RuntimeException(
                sprintf(
                    'L’exercice n’est pas équilibré : débit %.2f €, crédit %.2f €.',
                    $debit,
                    $credit
                )
            );
        }
    }

    private function creerNouvelExercice(
        Exercice $exercice
    ): Exercice {
        $nouveau = new Exercice();

        // Sécurisation avec "clone" pour éviter de modifier les dates de l'exercice courant
        $dateDebut = (clone $exercice->getDateFin())->modify('+1 day');
        $dateFin = (clone $dateDebut)->modify('+1 year')->modify('-1 day');

        $nom = sprintf(
            'Exercice %s',
            $dateDebut->format('Y')
        );

        $nouveau
            ->setNom($nom)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setActif(true)
            ->setCloture(false)
            ->setCopropriete($exercice->getCopropriete());

        $this->em->persist($nouveau);
        $this->em->flush();

        return $nouveau;
    }

    public function creerExerciceSuivant(
        Exercice $exercice
    ): Exercice {

        $suivant = $this->exerciceRepository
            ->findSuivant($exercice);

        if ($suivant) {
            return $suivant;
        }

        return $this->creerNouvelExercice($exercice);
    }

    public function genererANouveaux(
        Exercice $exercice
    ): void {
        $nouvelExercice =
            $this->exerciceRepository->findSuivant($exercice);

        if (!$nouvelExercice) {
            throw new \LogicException(
                'Aucun exercice suivant n’existe.'
            );
        }

        if (
            $this->operationRepository
            ->aNouveauxExistent($nouvelExercice)
        ) {
            throw new \LogicException(
                'Les à-nouveaux ont déjà été générés.'
            );
        }

        $anouveaux = $this->calculerANouveaux($exercice);

        if (empty($anouveaux->lignes)) {
            return;
        }

        if (!$anouveaux->estEquilibre()) {
            throw new \LogicException(
                'Les écritures d’à-nouveaux ne sont pas équilibrées.'
            );
        }

        $operation =
            $this->comptabiliteService->creerOperation(
                $nouvelExercice->getDateDebut(),
                sprintf(
                    'À-nouveaux - %s',
                    $nouvelExercice->getNom()
                ),
                OperationType::A_NOUVEAU
            );

        foreach ($anouveaux->lignes as $ligne) {
            if ((float) $ligne->debit > 0) {
                $this->comptabiliteService->creerDebit(
                    $operation,
                    $nouvelExercice,
                    $ligne->compte,
                    $ligne->debit,
                    $ligne->coproprietaire
                );
            }

            if ((float) $ligne->credit > 0) {
                $this->comptabiliteService->creerCredit(
                    $operation,
                    $nouvelExercice,
                    $ligne->compte,
                    $ligne->credit,
                    $ligne->coproprietaire
                );
            }
        }

        $this->comptabiliteService->enregistrer($operation);

        $this->em->flush();
    }
}
