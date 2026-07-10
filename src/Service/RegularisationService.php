<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Compte;
use App\Enum\OperationType;
use App\Repository\CoproprietaireRepository;
use App\Repository\EcritureRepository;
use App\Repository\LigneAppelFondRepository;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;

class RegularisationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CoproprietaireRepository $coproprietaireRepository,
        private EcritureRepository $ecritureRepository,
        private LigneAppelFondRepository $ligneAppelFondRepository,
        private ComptabiliteService $comptabiliteService,
        private CompteRepository $compteRepository,
    ) {}

    public function simulerRegularisation(Exercice $exercice): array
    {
        $copropriete = $exercice->getCopropriete();
        $tantiemesTotaux = $copropriete->getTantiemesBase();

        $totalChargesReelles = $this->ecritureRepository
            ->calculerTotalCharges($exercice);

        $bilan = [];
        $coproprietaires = $this->coproprietaireRepository->findAll();

        foreach ($coproprietaires as $copro) {
            $tantiemesCopro = 0;

            foreach ($copro->getLotCoproprietaires() as $lotCopro) {
                if ($lotCopro->getLot()->getCopropriete() === $copropriete) {
                    $tantiemesCopro += $lotCopro->getLot()->getTantiemes();
                }
            }

            if ($tantiemesCopro === 0) {
                continue;
            }

            $quotePartReelle = round(
                ((float) $totalChargesReelles * $tantiemesCopro) / $tantiemesTotaux,
                2
            );

            $totalAppele = $this->ligneAppelFondRepository
                ->calculerTotalAppele($exercice, $copro);

            $resultatRegularisation = round(
                (float) $totalAppele - $quotePartReelle,
                2
            );

            $bilan[$copro->getId()] = [
                'coproprietaire' => $copro,
                'tantiemes' => $tantiemesCopro,
                'totalAppele' => (float) $totalAppele,
                'quotePartReelle' => $quotePartReelle,
                'resultat' => $resultatRegularisation,
            ];
        }

        return [
            'totalChargesGlobales' => (float) $totalChargesReelles,
            'details' => $bilan,
        ];
    }

    public function genererRegularisation(Exercice $exercice): void
    {
        $simulation = $this->simulerRegularisation($exercice);

        if (empty($simulation['details'])) {
            return;
        }

        $compteResultat = $this->compteRepository
            ->findByNumeroOrFail('120000');

        if (!$compteResultat) {
            throw new \LogicException(
                'Le compte 120000 Résultat de l’exercice est introuvable.'
            );
        }

        $operation = $this->comptabiliteService->creerOperation(
            new \DateTimeImmutable(),
            'Régularisation des charges - ' . $exercice->getNom(),
            OperationType::REGULARISATION
        );

        $hasEcritures = false;

        foreach ($simulation['details'] as $detail) {
            $copro = $detail['coproprietaire'];
            $montant = round((float) $detail['resultat'], 2);

            if (abs($montant) < 0.01) {
                continue;
            }

            if (!$copro->getCompte()) {
                throw new \LogicException(
                    'Le copropriétaire ' . $copro . ' n’a pas de compte comptable associé.'
                );
            }

            if ($montant > 0) {
                // Trop appelé : on crédite le copropriétaire
                $this->comptabiliteService->creerCredit(
                    $operation,
                    $exercice,
                    $copro->getCompte(),
                    $montant,
                    $copro
                );

                $this->comptabiliteService->creerDebit(
                    $operation,
                    $exercice,
                    $compteResultat,
                    $montant
                );
            } else {
                // Pas assez appelé : on débite le copropriétaire
                $montantAbs = abs($montant);

                $this->comptabiliteService->creerDebit(
                    $operation,
                    $exercice,
                    $copro->getCompte(),
                    $montantAbs,
                    $copro
                );

                $this->comptabiliteService->creerCredit(
                    $operation,
                    $exercice,
                    $compteResultat,
                    $montantAbs
                );
            }

            $hasEcritures = true;
        }

        if (!$hasEcritures) {
            return;
        }

        $this->comptabiliteService->enregistrer($operation);

        $exercice->setRegularisationsGenerees(true);

        $this->em->flush();
    }
}
