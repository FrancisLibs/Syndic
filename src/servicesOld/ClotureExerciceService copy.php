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

    public function __construct(
        ExerciceRepository $exerciceRepository,
        CompteRepository $compteRepository,
        EntityManagerInterface $em,
        EcritureRepository $ecritureRepository,
        OperationRepository $operationRepository,
    ) {
        $this->compteRepository = $compteRepository;
        $this->exerciceRepository = $exerciceRepository;
        $this->ecritureRepository = $ecritureRepository;
        $this->operationRepository = $operationRepository;
        $this->em = $em;
    }

    public function executerCloture(Exercice $exercice): Exercice
    {
        // dd('ok');
        // Vérifier si tout est bon pour la clôture
        // $this->verifierExercice($exercice);

        $regularisations =
            $this->calculerRegularisations($exercice);

        // ÉTAPE 0 : Créer un nouvel exercice (On stocke bien la variable !)
        $nouvelExercice = $this->creerNouvelExercice($exercice);

        // ETAPE 1 : basculer les deux exercices
        $this->basculer($exercice);

        // // ÉTAPE 1 : Calculer et générer les régularisations de charges
        // $this->regulariserCharges($exercice);

        // // ÉTAPE 2 : Générer les écritures de report (À-nouveaux) pour l'exercice suivant
        // $this->genererAnouveau($exercice, $nouvelExercice);

        // ÉTAPE 3 : Verrouiller l'exercice (On utilise ta méthode du bas)
        // $this->cloturerExercice($exercice);

        $this->em->flush();

        return $nouvelExercice;
    }

    private function verifierExercice(Exercice $exercice): void
    {

        // =====================
        // Statut exercice
        // =====================

        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Exercice déjà clôturé'
            );
        }

        if (!$exercice->isActif()) {
            throw new \LogicException(
                'Exercice non actif'
            );
        }

        // =====================
        // Budgets
        // =====================

        foreach ($exercice->getBudgets() as $budget) {

            if (!$budget->isVerrouille()) {
                throw new \LogicException(
                    sprintf(
                        'Budget "%s" non verrouillé',
                        $budget->getLibelle()
                    )
                );
            }
        }

        // =====================
        // Opérations
        // =====================

        $operations = [];

        foreach ($exercice->getEcritures() as $ecriture) {

            $operation = $ecriture->getOperation();

            if (!$operation) {
                continue;
            }

            $id = $operation->getId();

            if (isset($operations[$id])) {
                continue;
            }

            if (!$operation->isEquilibree()) {
                throw new \LogicException(
                    sprintf(
                        'Opération "%s" non équilibrée',
                        $operation->getLibelle()
                    )
                );
            }

            $operations[$id] = true;
        }
    }

    private function creerNouvelExercice(Exercice $exercice): Exercice
    {
        $nouveau = new Exercice();

        $anneeExercice =  new \DateTime()->format('Y') + 1;
        settype($anneeExercice, 'string');
        $nom = ('Exercice ' . $anneeExercice);

        // 🛡️ Sécurisation avec "clone" pour éviter de corrompre les dates4
        $dateDebut = (clone $exercice->getDateFin())->modify('+1 day');
        $dateFin = (clone $dateDebut)->modify('+1 year')->modify('-1 day');

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

    private function basculer(Exercice $exercice): Exercice
    {
        if ($exercice->isCloture()) {
            throw new \LogicException(
                'Exercice déjà clôturé'
            );
        }

        // Recherche exercice suivant
        $exerciceSuivant = $this->exerciceRepository->findSuivant($exercice);

        // Bascule
        $exercice->setActif(false);
        $exercice->setCloture(true);
        $exerciceSuivant->setActif(true);

        $this->em->flush();

        return $exerciceSuivant;
    }

    private function regulariserCharges(Exercice $exercice): void
    {
        $copropriete = $exercice->getCopropriete();
        $tantiemesTotaux = $copropriete->getTantiemesBase(); // ex: 10000

        // 1. Calculer le total des charges réelles de l'exercice
        // Via une requête QueryBuilder sur FactureFournisseur
        $totalDepenses = $this->em->createQueryBuilder()
            ->select('SUM(f.montant)')
            ->from(\App\Entity\FactureFournisseur::class, 'f')
            ->where('f.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        if ($totalDepenses == 0) {
            return; // Pas de dépenses, rien à régulariser
        }

        // 2. Récupérer les appels de fonds globaux par copropriétaire sur cet exercice
        $appelsParCopro = [];
        foreach ($exercice->getBudgets() as $budget) {
            foreach ($budget->getAppels() as $appel) {
                foreach ($appel->getLigneAppelFonds() as $ligne) {
                    $coproId = $ligne->getCoproprietaire()->getId();
                    if (!isset($appelsParCopro[$coproId])) {
                        $appelsParCopro[$coproId] = 0;
                    }
                    $appelsParCopro[$coproId] += $ligne->getMontant();
                }
            }
        }

        // 3. Créer l'opération de régularisation globale
        $operation = new \App\Entity\Operation();
        $operation->setDate(new \DateTimeImmutable())
            ->setLibelle('Régularisation des charges - ' . $exercice->getNom())
            ->setType(OperationType::REGULARISATION)
            ->setStatut(OperationStatut::VALIDE);
        $this->em->persist($operation);

        // 4. Calculer la quote-part par lot et par copropriétaire
        $lots = $copropriete->getLots();
        $repartitionCopro = [];

        foreach ($lots as $lot) {
            // Calcul de la part du lot
            $partLot = ($totalDepenses * $lot->getTantiemes()) / $tantiemesTotaux;

            // Trouver le copropriétaire actif du lot (via lotCoproprietaires)
            foreach ($lot->getLotCoproprietaires() as $lotCopro) {
                if ($lotCopro->getDateFin() === null) { // Le propriétaire actuel
                    $copro = $lotCopro->getCoproprietaire();
                    if (!isset($repartitionCopro[$copro->getId()])) {
                        $repartitionCopro[$copro->getId()] = [
                            'entite' => $copro,
                            'part_charges' => 0
                        ];
                    }
                    $repartitionCopro[$copro->getId()]['part_charges'] += $partLot;
                }
            }
        }

        // 5. Générer les écritures comptables d'ajustement
        foreach ($repartitionCopro as $coproId => $data) {
            $copro = $data['entite'];
            $chargesReelles = $data['part_charges'];
            $appelsVerses = $appelsParCopro[$coproId] ?? 0;

            $soldeRegul = $appelsVerses - $chargesReelles;

            $ecritureCopro = new \App\Entity\Ecriture();
            $ecritureCopro->setOperation($operation)
                ->setExercice($exercice)
                ->setCompte($copro->getCompte()) // Ton champ compte_id dans coproprietaire (Classe 450xxx)
                ->setCoproprietaire($copro)
                ->setDate(new \DateTimeImmutable());

            if ($soldeRegul > 0) {
                // Le copropriétaire a trop payé -> On crédite son compte (le syndic lui doit de l'argent)
                $ecritureCopro->setCredit($soldeRegul);
                $ecritureCopro->setDebit(0);
            } else {
                // Le copropriétaire n'a pas assez payé -> On débite son compte (il doit de l'argent)
                $ecritureCopro->setDebit(abs($soldeRegul));
                $ecritureCopro->setCredit(0);
            }
            $this->em->persist($ecritureCopro);

            // TODO: Contrepartie comptable globale (ex: Débit/Crédit sur le compte 701000 ou 120000)
            // Pour que ta balance générale reste à zéro (Principe de la partie double).
        }
    }

    public function calculerRegularisations(
        Exercice $exercice
    ): array {

        $resultat = [];

        $coproprietaires = [];

        $copropriete = $exercice->getCopropriete();

        foreach ($copropriete->getLots() as $lot) {

            foreach ($lot->getLotCoproprietaires() as $relation) {

                $copro = $relation->getCoproprietaire();

                if ($copro) {
                    $coproprietaires[$copro->getId()] = $copro;
                }
            }
        }

        // dd($coproprietaires);

        foreach ($coproprietaires as $copro) {

            $totalCharges = '0.00';
            $totalAppels = '0.00';

            // Charges réelles
            foreach ($copro->getRepartitions() as $repartition) {

                $ecriture = $repartition->getEcriture();

                if (
                    $ecriture->getExercice()?->getId()
                    !== $exercice->getId()
                ) {
                    continue;
                }

                $totalCharges = bcadd(
                    $totalCharges,
                    $repartition->getMontant(),
                    2
                );
            }

            // Appels de fonds
            foreach ($copro->getLigneAppelFonds() as $ligne) {

                $appel = $ligne->getAppelFond();

                if (!$appel) {
                    continue;
                }

                $budget = $appel->getBudget();

                if (
                    !$budget
                    || !$budget->getExercice()
                    || $budget->getExercice()->getId() !== $exercice->getId()
                ) {
                    continue;
                }

                $totalAppels = bcadd(
                    $totalAppels,
                    $ligne->getMontant(),
                    2
                );
            }

            $regularisation = bcsub(
                $totalCharges,
                $totalAppels,
                2
            );

            $resultat[] = [
                'coproprietaire' => $copro,
                'charges' => $totalCharges,
                'appels' => $totalAppels,
                'regularisation' => $regularisation,
            ];
        }

        return $resultat;
    }

    public function genererRegularisations(
        Exercice $exercice
    ): void {
        $regularisations =
            $this->calculerRegularisations(
                $exercice
            );

        foreach ($regularisations as $ligne) {

            $montant =
                (float) $ligne['regularisation'];

            if (
                abs($montant) < 0.01
            ) {
                continue;
            }

            $this->genererRegularisationCoproprietaire(
                $exercice,
                $ligne['coproprietaire'],
                $montant
            );
        }

        $exercice->setRegularisationsGenerees(true);

        $this->em->flush();
    }

    private function genererRegularisationCoproprietaire(
        Exercice $exercice,
        Coproprietaire $coproprietaire,
        float $montant
    ): void {
        $compteResultat =
            $this->compteRepository
            ->findByNumero('120000');

        if (!$compteResultat) {
            throw new \LogicException(
                'Compte 120000 introuvable'
            );
        }

        $operation = new Operation();

        $operation->setDate(
            $exercice->getDateFin()
        );

        $operation->setLibelle(
            sprintf(
                'Régularisation %s',
                $coproprietaire
            )
        );

        $operation->setType(
            OperationType::REGULARISATION
        );

        // suite...
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

    /**
     * @return SoldeReportable[]
     */
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

        $estBloque =
            !$exercice->isActif()
            || $exercice->isCloture()
            || !$budgetsVerrouilles
            || !$operationsEquilibrees
            || !empty($erreurs);

        return new EtatCloture(
            exerciceActif: $exercice->isActif(),
            exerciceCloture: $exercice->isCloture(),
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
            $this->compteRepository->findByNumero('120000');

        if (!$compteResultat) {
            throw new \LogicException(
                'Le compte 120000 est introuvable.'
            );
        }

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

    private function creerOperation(
        \DateTimeImmutable $date,
        string $libelle,
        OperationType $type
    ): Operation {

        $operation = new Operation();

        $operation
            ->setDate($date)
            ->setLibelle($libelle)
            ->setType($type)
            ->setStatut(OperationStatut::VALIDE);

        return $operation;
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
            $this->creerOperation(
                $exercice->getDateFin(),
                sprintf(
                    'Clôture des comptes de gestion - %s',
                    $exercice->getNom()
                ),
                OperationType::CLOTURE
            );

        foreach ($cloture->lignes as $ligne) {

            $ecriture = new Ecriture();

            $ecriture
                ->setCompte($ligne->compte)
                ->setDebit($ligne->debit)
                ->setCredit($ligne->credit)
                ->setDate($exercice->getDateFin())
                ->setExercice($exercice);

            $operation->addEcriture($ecriture);
        }

        $this->em->persist($operation);

        $this->em->flush();
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

    public function getOperationClotureComptesGestion(
        Exercice $exercice
    ): ?Operation {
        return $this->operationRepository
            ->findClotureComptesGestion($exercice);
    }

    /**
     * @return ClotureCompteGestionLigne[]
     */
    public function getLignesClotureComptesGestion(
        Exercice $exercice
    ): array {
        $operation =
            $this->getOperationClotureComptesGestion($exercice);

        if (!$operation) {
            return $this->calculerClotureComptesGestion($exercice)->lignes;
        }

        $lignes = [];

        foreach ($operation->getEcritures() as $ecriture) {
            $lignes[] = new ClotureCompteGestionLigne(
                compte: $ecriture->getCompte(),
                debit: (float) $ecriture->getDebit(),
                credit: (float) $ecriture->getCredit(),
            );
        }

        return $lignes;
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

    public function genererANouveaux(
        Exercice $exercice
    ): void {
        $nouvelExercice =
            $this->exerciceRepository->findSuivant($exercice);

        if (!$nouvelExercice) {
            throw new \LogicException(
                'Aucun exercice suivant n’existe. Créez d’abord le nouvel exercice.'
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

        $anouveaux =
            $this->calculerANouveaux($exercice);

        if (empty($anouveaux->lignes)) {
            return;
        }

        if (!$anouveaux->estEquilibre()) {
            throw new \LogicException(
                'Les écritures d’à-nouveaux ne sont pas équilibrées.'
            );
        }

        $operation =
            $this->creerOperation(
                $nouvelExercice->getDateDebut(),
                sprintf(
                    'À-nouveaux - %s',
                    $nouvelExercice->getNom()
                ),
                OperationType::A_NOUVEAU
            );

        foreach ($anouveaux->lignes as $ligne) {
            $ecriture = new Ecriture();

            $ecriture
                ->setCompte($ligne->compte)
                ->setCoproprietaire($ligne->coproprietaire)
                ->setDebit($ligne->debit)
                ->setCredit($ligne->credit)
                ->setDate($nouvelExercice->getDateDebut())
                ->setExercice($nouvelExercice);

            $operation->addEcriture($ecriture);
        }

        $this->em->persist($operation);
        $this->em->flush();
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
}
