<?php

namespace App\Repository;

use App\Entity\Ecriture;
use App\Entity\Exercice;
use App\Entity\Lot;
use App\Entity\Compte;
use App\Enum\CompteType;
use App\Enum\OperationType;
use App\Enum\OperationStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ecriture>
 */
class EcritureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ecriture::class);
    }

    public function findByLotAndCompteType(Lot $lot, CompteType $type): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.compte', 'c')
            ->where('e.lot = :lot')
            ->andWhere('c.type = :type')
            ->setParameter('lot', $lot)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function calculerTotauxParExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.debit), 0) AS debit')
            ->addSelect('COALESCE(SUM(e.credit), 0) AS credit')
            ->join('e.operation', 'o')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->getQuery()
            ->getSingleResult();
    }

    public function calculerSoldesReportables(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.id AS compteId')
            ->addSelect('cp.id AS coproprietaireId')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->join('e.operation', 'o')
            ->leftJoin('e.coproprietaire', 'cp')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.statut = :statut')
            ->andWhere('c.type IN (:typesReportables)')
            ->setParameter('exercice', $exercice)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->setParameter('typesReportables', [
                CompteType::ACTIF,
                CompteType::PASSIF,
                CompteType::TIERS,
                CompteType::BANQUE,
            ])
            ->groupBy('c.id')
            ->addGroupBy('cp.id')
            ->having('SUM(e.debit) <> SUM(e.credit)')
            ->orderBy('c.numero', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerTotauxChargesProduits(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.type AS type')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->join('e.operation', 'o')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.statut = :statut')
            ->andWhere('c.type IN (:types)')
            ->setParameter('exercice', $exercice)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->setParameter('types', [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ])
            ->groupBy('c.type')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerSoldesComptesGestion(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->select('c.id AS compteId')
            ->addSelect('SUM(e.debit) AS debit')
            ->addSelect('SUM(e.credit) AS credit')
            ->join('e.compte', 'c')
            ->join('e.operation', 'o')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.statut = :statut')
            ->andWhere('c.type IN (:types)')
            ->setParameter('exercice', $exercice)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->setParameter('types', [
                CompteType::CHARGE,
                CompteType::PRODUIT,
            ])
            ->groupBy('c.id')
            ->orderBy('c.numero')
            ->getQuery()
            ->getArrayResult();
    }

    public function calculerTotalCharges(
        Exercice $exercice
    ): float {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.debit) - SUM(e.credit)')
            ->join('e.compte', 'c')
            ->join('e.operation', 'o')
            ->where('e.exercice = :exercice')
            ->andWhere('c.type = :type')
            ->andWhere('o.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter('type', CompteType::CHARGE)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Retourne les écritures de débit correspondant aux charges
     * comptabilisées pour un exercice.
     *
     * @return Ecriture[]
     */
    public function findChargesAvecRepartitions(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('e')
            ->addSelect(
                'operation',
                'compte',
                'repartitions',
                'typeCharge',
                'compteCharge'
            )
            ->join('e.operation', 'operation')
            ->join('e.compte', 'compte')
            ->leftJoin('e.repartitions', 'repartitions')
            ->join('operation.typeCharge', 'typeCharge')
            ->join('typeCharge.compte', 'compteCharge')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('operation.type IN (:types)')
            ->andWhere('e.compte = compteCharge')
            ->andWhere('operation.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'types',
                [
                    OperationType::CHARGE,
                    OperationType::FNP,
                ]
            )
            ->setParameter('statut', OperationStatut::VALIDE)
            ->orderBy('operation.date', 'ASC')
            ->addOrderBy('operation.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculerSoldeCompte(
        Exercice $exercice,
        Compte $compte
    ): float {
        $resultat = $this->createQueryBuilder('e')
            ->select(
                'COALESCE(SUM(e.debit) - SUM(e.credit), 0)'
            )
            ->join('e.operation', 'o')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('e.compte = :compte')
            ->andWhere('o.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter('compte', $compte)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $resultat;
    }

    public function calculerSoldeCompteADate(
        Exercice $exercice,
        Compte $compte,
        \DateTimeInterface $date
    ): float {
        $resultat = $this->createQueryBuilder('e')
            ->select(
                'COALESCE(SUM(e.debit) - SUM(e.credit), 0)'
            )
            ->join('e.operation', 'o')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('e.compte = :compte')
            ->andWhere('e.date <= :date')
            ->andWhere('o.statut = :statut')
            ->setParameter('exercice', $exercice)
            ->setParameter('compte', $compte)
            ->setParameter('date', $date)
            ->setParameter('statut', OperationStatut::VALIDE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $resultat;
    }

    public function calculerRealiseParTypeCharge(
        Exercice $exercice
    ): array {
        $resultats = $this->createQueryBuilder('e')
            ->select('tc.id AS typeChargeId')
            ->addSelect('IDENTITY(tc.typeChargeBudget) AS typeChargeBudgetId')
            ->addSelect('SUM(e.debit) - SUM(e.credit) AS montant')
            ->join('e.operation', 'o')
            ->join('e.compte', 'c')
            ->join('o.typeCharge', 'tc')
            ->andWhere('e.exercice = :exercice')
            ->andWhere('o.statut = :statut')
            ->andWhere('c.type = :typeCompte')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'statut',
                OperationStatut::VALIDE
            )
            ->setParameter(
                'typeCompte',
                CompteType::CHARGE
            )
            ->groupBy('tc.id')
            ->addGroupBy('tc.typeChargeBudget')
            ->getQuery()
            ->getArrayResult();

        $regroupes = [];

        foreach ($resultats as $ligne) {

            $typeChargeId = $ligne['typeChargeBudgetId']
                ? (int) $ligne['typeChargeBudgetId']
                : (int) $ligne['typeChargeId'];

            if (!isset($regroupes[$typeChargeId])) {
                $regroupes[$typeChargeId] = [
                    'typeChargeId' => $typeChargeId,
                    'montant' => 0.0,
                ];
            }

            $regroupes[$typeChargeId]['montant'] +=
                (float) $ligne['montant'];
        }

        return array_values($regroupes);
    }

    public function calculerBalance(Exercice $exercice): array
    {
        $ecritures = $this->findBy([
            'exercice' => $exercice,
        ]);

        $balance = [];

        foreach ($ecritures as $ecriture) {
            if (
                $ecriture->getOperation()->getStatut()
                !== OperationStatut::VALIDE
            ) {
                continue;
            }

            $compte = $ecriture->getCompte();
            $numero = $compte->getNumero();

            if (!isset($balance[$numero])) {
                $balance[$numero] = [
                    'numero' => $numero,
                    'libelle' => $compte->getLibelle(),
                    'debit' => 0.0,
                    'credit' => 0.0,
                ];
            }

            $balance[$numero]['debit'] +=
                (float) $ecriture->getDebit();

            $balance[$numero]['credit'] +=
                (float) $ecriture->getCredit();
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalSoldeDebiteur = 0.0;
        $totalSoldeCrediteur = 0.0;

        foreach ($balance as &$ligne) {
            $solde = round(
                $ligne['debit'] - $ligne['credit'],
                2
            );

            $ligne['soldeDebiteur'] = 0.0;
            $ligne['soldeCrediteur'] = 0.0;

            if ($solde > 0) {
                $ligne['soldeDebiteur'] = $solde;
            } elseif ($solde < 0) {
                $ligne['soldeCrediteur'] = abs($solde);
            }

            $totalDebit += $ligne['debit'];
            $totalCredit += $ligne['credit'];
            $totalSoldeDebiteur += $ligne['soldeDebiteur'];
            $totalSoldeCrediteur += $ligne['soldeCrediteur'];
        }

        unset($ligne);

        ksort($balance);

        return [
            'balance' => $balance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'totalSoldeDebiteur' => $totalSoldeDebiteur,
            'totalSoldeCrediteur' => $totalSoldeCrediteur,
            'equilibree' =>
            abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}
