<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\FactureFournisseurType;


class FactureFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureFournisseur::class);
    }

    // src/Repository/FactureFournisseurRepository.php

    public function findPourExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.typeCharge', 'tc')
            ->addSelect('tc')
            ->andWhere('f.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->orderBy('tc.nom', 'ASC')
            ->addOrderBy('f.dateFacture', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FactureFournisseur[]
     */
    public function findFacturesEauByExercice(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('facture')
            ->innerJoin('facture.typeCharge', 'typeCharge')
            ->addSelect('typeCharge')
            ->andWhere('facture.exercice = :exercice')
            ->andWhere('typeCharge.estEau = :estEau')
            ->andWhere('facture.comptabilisee = :comptabilisee')
            ->setParameter('exercice', $exercice)
            ->setParameter('estEau', true)
            ->setParameter('comptabilisee', true)
            ->orderBy('facture.dateFacture', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculerTotalFacturesEau(
        Exercice $exercice
    ): string {
        return (string) $this->createQueryBuilder('facture')
            ->select(
                'COALESCE(SUM(
                CASE
                    WHEN facture.type = :typeAvoir
                    THEN -facture.montant
                    ELSE facture.montant
                END
            ), 0)'
            )
            ->innerJoin('facture.typeCharge', 'typeCharge')
            ->andWhere('facture.exercice = :exercice')
            ->andWhere('typeCharge.estEau = true')
            ->andWhere('facture.comptabilisee = true')
            ->setParameter('exercice', $exercice)
            ->setParameter(
                'typeAvoir',
                FactureFournisseurType::AVOIR
            )
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAvecOperationPourCorrection(
        int $id
    ): ?FactureFournisseur {
        return $this->createQueryBuilder('f')
            ->addSelect('o', 'e', 'r')
            ->leftJoin('f.operation', 'o')
            ->leftJoin('o.ecritures', 'e')
            ->leftJoin('e.repartitions', 'r')
            ->andWhere('f.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return FactureFournisseur[]
     */
    public function findFnpATraiter(
        Exercice $exercice
    ): array {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.exercice', 'e')
            ->addSelect('e')
            ->leftJoin('f.fournisseur', 'fournisseur')
            ->addSelect('fournisseur')
            ->leftJoin('f.typeCharge', 'typeCharge')
            ->addSelect('typeCharge')
            ->andWhere('f.fnp = :fnp')
            ->andWhere('f.fnpTraitee = :traitee')
            ->andWhere('e.dateFin < :dateDebut')
            ->setParameter('fnp', true)
            ->setParameter('traitee', false)
            ->setParameter('dateDebut', $exercice->getDateDebut())
            ->orderBy('e.dateFin', 'ASC')
            ->addOrderBy('f.dateFacture', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFnpCorrespondante(
        Fournisseur $fournisseur,
        ?string $numero,
        string $montant
    ): ?FactureFournisseur {
        if ($numero === null || trim($numero) === '') {
            return null;
        }

        return $this->createQueryBuilder('f')
            ->andWhere('f.fournisseur = :fournisseur')
            ->andWhere('f.fnp = true')
            ->andWhere('f.fnpTraitee = false')
            ->andWhere('f.numero = :numero')
            ->andWhere('f.montant = :montant')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('numero', trim($numero))
            ->setParameter('montant', $montant)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDoublon(
        Fournisseur $fournisseur,
        ?string $numero,
        string $montant
    ): ?FactureFournisseur {

        $numeroNormalise = $this->normaliserNumero($numero);

        if ($numeroNormalise === '') {
            return null;
        }

        $factures = $this->createQueryBuilder('f')
            ->andWhere('f.fournisseur = :fournisseur')
            ->andWhere('f.montant = :montant')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('montant', $montant)
            ->getQuery()
            ->getResult();

        foreach ($factures as $facture) {

            if (
                $this->normaliserNumero($facture->getNumero())
                === $numeroNormalise
            ) {
                return $facture;
            }
        }

        return null;
    }

    private function normaliserNumero(?string $numero): string
    {
        $numero = mb_strtoupper(trim($numero ?? ''));

        // Supprime le préfixe éventuel "Facture"
        $numero = preg_replace('/^FACTURE\s*/u', '', $numero);

        // Supprime les séparateurs
        return preg_replace('/[\s\-\/\.]+/u', '', $numero);
    }

    public function findCorrespondancePossible(
        Fournisseur $fournisseur,
        string $montant
    ): ?FactureFournisseur {
        return $this->createQueryBuilder('f')
            ->andWhere('f.fournisseur = :fournisseur')
            ->andWhere('f.montant = :montant')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('montant', $montant)
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
