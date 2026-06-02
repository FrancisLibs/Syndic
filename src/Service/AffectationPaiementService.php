<?php

namespace App\Service;

use App\Entity\AffectationPaiement;
use App\Entity\Paiement;
use App\Repository\LigneAppelFondRepository;
use Doctrine\ORM\EntityManagerInterface;

class AffectationPaiementService
{
    public function __construct(
        private LigneAppelFondRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function affecter(
        Paiement $paiement
    ): void {

        $restePaiement = (float) $paiement->getMontant();

        // =====================
        // Lignes ouvertes
        // =====================

        $lignes = $this->repository
            ->createQueryBuilder('l')
            ->where('l.coproprietaire = :copro')
            ->andWhere('l.soldee = false')
            ->setParameter(
                'copro',
                $paiement->getCoproprietaire()
            )
            ->orderBy('l.id', 'ASC')

            ->getQuery()
            ->getResult();

        foreach ($lignes as $ligne) {

            if ($restePaiement <= 0) {
                break;
            }

            $resteLigne =
                $ligne->getResteAPayer();

            if ($resteLigne <= 0) {
                continue;
            }

            $montantAffecte =
                min(
                    $restePaiement,
                    $resteLigne
                );

            // =====================
            // Affectation
            // =====================

            $affectation =
                new AffectationPaiement();

            $affectation->setPaiement(
                $paiement
            );

            $affectation->setLigneAppel(
                $ligne
            );

            $affectation->setMontant(
                number_format(
                    $montantAffecte,
                    2,
                    '.',
                    ''
                )
            );

            // =====================
            // Mise à jour ligne
            // =====================

            $nouveauRegle = (float) $ligne->getMontantRegle()
                + $montantAffecte;

            $ligne->setMontantRegle(
                number_format(
                    $nouveauRegle,
                    2,
                    '.',
                    ''
                )
            );

            if (
                $ligne->getResteAPayer() <= 0.01
            ) {

                $ligne->setSoldee(true);
            }

            // =====================
            // Paiement restant
            // =====================

            $restePaiement -= $montantAffecte;

            $this->entityManager->persist($affectation);
        }

        $this->entityManager->flush();
    }
}
