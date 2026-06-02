<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Ecriture;
use App\Entity\LigneEcriture;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurEcritureAppelFondService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompteRepository $compteRepository,
    ) {}

    public function generer(
        AppelFond $appelFond
    ): Ecriture {

        // =====================
        // Compte produit
        // =====================

        $compteProduit = $this->compteRepository
            ->findOneBy(
                [
                    'numero' => '701000'
                ]
            );

        if (!$compteProduit) {

            throw new \Exception(
                'Compte 701000 introuvable'
            );
        }

        // =====================
        // Écriture
        // =====================

        $ecriture = new Ecriture();

        $ecriture->setDate(
            $appelFond->getDateAppel()
        );

        $ecriture->setLibelle(
            $appelFond->getLibelle()
        );

        // =====================
        // Lignes copro
        // =====================

        foreach (
            $appelFond->getLigneAppelFonds()
            as $ligneAppel
        ) {

            $compteCopro =
                $ligneAppel
                ->getCoproprietaire()
                ->getCompte();

            // =====================
            // Débit copro
            // =====================

            $debit =
                new LigneEcriture();

            $debit->setCompte(
                $compteCopro
            );

            $debit->setDebit(
                $ligneAppel->getMontant()
            );

            $debit->setCredit(0);

            $debit->setLibelle(
                $appelFond->getLibelle()
            );

            $debit->setEcriture(
                $ecriture
            );

            $ecriture
                ->addLigne($debit);

            $this->entityManager
                ->persist($debit);

            // =====================
            // Crédit produit
            // =====================

            $credit =
                new LigneEcriture();

            $credit->setCompte(
                $compteProduit
            );

            $credit->setDebit(0);

            $credit->setCredit(
                $ligneAppel->getMontant()
            );

            $credit->setLibelle(
                $appelFond->getLibelle()
            );

            $credit->setEcriture(
                $ecriture
            );

            $ecriture
                ->addLigne($credit);

            $this->entityManager
                ->persist($credit);
        }

        // =====================
        // Sauvegarde
        // =====================

        $this->entityManager
            ->persist($ecriture);

        $this->entityManager
            ->flush();

        return $ecriture;
    }
}
