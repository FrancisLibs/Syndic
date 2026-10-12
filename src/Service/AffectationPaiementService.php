<?php

namespace App\Service;

use App\Entity\AffectationPaiement;
use App\Entity\Paiement;
use App\Repository\LigneAppelFondRepository;
use App\Repository\Travaux\ChantierRepository;
use Doctrine\ORM\EntityManagerInterface;

class AffectationPaiementService
{
    public function __construct(
        private LigneAppelFondRepository $repository,
        private ChantierRepository $chantierRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function affecter(
        Paiement $paiement,
        float $montantCharges,
        array $montantsChantiers
    ): void {

        $montantPaiement = (int) round(
            (float) $paiement->getMontant() * 100
        );

        if (
            !is_finite($montantCharges) ||
            $montantCharges < 0 ||
            abs($montantCharges * 100 - round($montantCharges * 100)) > 0.000001
        ) {
            throw new \LogicException(
                'Le montant affecté aux charges est invalide.'
            );
        }

        $chargesCentimes = (int) round($montantCharges * 100);

        $totalAffecte = $chargesCentimes;
        $chantiers = [];

        // Vérification des montants des chantiers
        foreach ($montantsChantiers as $chantierId => $montant) {

            if (
                !is_scalar($montant) ||
                !is_numeric($montant)
            ) {
                throw new \LogicException(
                    'Un montant de chantier est invalide.'
                );
            }

            $montant = (float) $montant;

            if (
                !is_finite($montant) ||
                $montant < 0 ||
                abs($montant * 100 - round($montant * 100)) > 0.000001
            ) {
                throw new \LogicException(
                    'Un montant de chantier est invalide.'
                );
            }

            $centimes = (int) round($montant * 100);

            if ($centimes === 0) {
                continue;
            }

            $chantier = $this->chantierRepository->find($chantierId);

            if ($chantier === null || $chantier->isCloture()) {
                throw new \LogicException(
                    'Le chantier sélectionné est inexistant ou clôturé.'
                );
            }

            if (
                $chantier->getCopropriete()?->getId() !==
                $paiement->getExercice()?->getCopropriete()?->getId()
            ) {
                throw new \LogicException(
                    'Le chantier ne correspond pas à la copropriété du paiement.'
                );
            }

            $chantiers[] = [
                'chantier' => $chantier,
                'centimes' => $centimes,
            ];

            $totalAffecte += $centimes;
        }

        if ($montantPaiement <= 0) {
            throw new \LogicException(
                'Le montant du paiement doit être positif.'
            );
        }

        if ($totalAffecte > $montantPaiement) {
            throw new \LogicException(
                'Le total ventilé dépasse le montant du paiement.'
            );
        }

        // Charges courantes
        if ($chargesCentimes > 0) {

            $lignes = $this->repository->findLignesChargesARegler(
                $paiement->getCoproprietaire(),
                $paiement->getExercice()
            );

            $this->affecterAuxLignes(
                $paiement,
                $lignes,
                $chargesCentimes,
                'charges courantes'
            );
        }

        // Chantiers
        foreach ($chantiers as $chantierData) {

            $chantier = $chantierData['chantier'];

            $lignes = $this->repository->findLignesChantierARegler(
                $paiement->getCoproprietaire(),
                $chantier
            );

            $this->affecterAuxLignes(
                $paiement,
                $lignes,
                $chantierData['centimes'],
                'chantier ' . $chantier->getLibelle()
            );
        }

        $this->entityManager->flush();
    }

    /**
     * Affecte un montant aux lignes d'appels
     * et actualise leur règlement.
     */
    private function affecterAuxLignes(
        Paiement $paiement,
        array $lignes,
        int $montantCentimes,
        string $designation
    ): void {

        $reste = $montantCentimes;

        foreach ($lignes as $ligne) {

            if ($reste <= 0) {
                break;
            }

            $resteLigne = (int) round(
                $ligne->getResteAPayer() * 100
            );

            if ($resteLigne <= 0) {
                continue;
            }

            $centimes = min($reste, $resteLigne);

            $affectation = new AffectationPaiement();

            $affectation
                ->setPaiement($paiement)
                ->setLigneAppel($ligne)
                ->setMontant(
                    number_format($centimes / 100, 2, '.', '')
                );

            $this->entityManager->persist($affectation);

            $nouveauRegle = (int) round(
                (float) $ligne->getMontantRegle() * 100
            ) + $centimes;

            $ligne->setMontantRegle(
                number_format($nouveauRegle / 100, 2, '.', '')
            );

            $ligne->setSoldee(
                $ligne->getResteAPayer() <= 0.001
            );

            $reste -= $centimes;
        }

        if ($reste > 0) {
            throw new \LogicException(sprintf(
                'Le montant affecté aux %s dépasse les appels restant à régler.',
                $designation
            ));
        }
    }
}
