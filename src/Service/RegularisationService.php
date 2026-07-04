<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Operation;
use App\Entity\Ecriture;
use App\Enum\OperationType;
use App\Enum\OperationStatut;
use App\Repository\CoproprietaireRepository;
use App\Repository\EcritureRepository;
use App\Repository\LigneAppelFondRepository;
use Doctrine\ORM\EntityManagerInterface;

class RegularisationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CoproprietaireRepository $coproprietaireRepository,
        private EcritureRepository $ecritureRepository,
        private LigneAppelFondRepository $ligneAppelFondRepository,
    ) {}

    /**
     * Calcule la régularisation pour chaque copropriétaire sans écrire en BDD.
     */
    public function simulerRegularisation(Exercice $exercice): array
    {
        $copropriete = $exercice->getCopropriete();
        $tantiemesTotaux = $copropriete->getTantiemesBase(); // 10000 dans ton SQL

        // 1. Calculer le total des charges réelles de l'exercice
        // Il est plus rigoureux de se baser sur les écritures de type 'charge' (Classe 6)
        $totalChargesReelles = $this->em->createQuery(
            'SELECT SUM(e.debit) - SUM(e.credit) 
             FROM App\Entity\Ecriture e 
             JOIN e.compte c 
             WHERE e.exercice = :exercice 
             AND c.type = :type'
        )->setParameters([
            'exercice' => $exercice,
            'type' => 'charge'
        ])->getSingleScalarResult() ?? 0.0;

        $bilan = [];
        $coproprietaires = $this->coproprietaireRepository->findAll();

        foreach ($coproprietaires ?? [] as $copro) {
            // 2. Calculer les tantièmes cumulés du copropriétaire pour cette copropriété
            $tantiemesCopro = 0;
            foreach ($copro->getLotCoproprietaires() as $lotCopro) {
                if ($lotCopro->getLot()->getCopropriete() === $copropriete) {
                    // Optionnel : ajouter un filtre si date_fin de lot_coproprietaire est renseignée
                    $tantiemesCopro += $lotCopro->getLot()->getTantiemes();
                }
            }

            if ($tantiemesCopro === 0) {
                continue;
            }

            // 3. Calculer la quote-part réelle des charges
            $quotePartReelle = round(($totalChargesReelles * $tantiemesCopro) / $tantiemesTotaux, 2);

            // 4. Calculer le total qui lui a été appelé durant l'exercice
            $totalAppele = $this->em->createQuery(
                'SELECT SUM(laf.montant) 
                 FROM App\Entity\LigneAppelFond laf 
                 JOIN laf.appelFond af 
                 JOIN af.budget b 
                 WHERE b.exercice = :exercice 
                 AND laf.coproprietaire = :copro'
            )->setParameters([
                'exercice' => $exercice,
                'copro' => $copro
            ])->getSingleScalarResult() ?? 0.0;

            // 5. Résultat de la régularisation (Appelé - Réel)
            // Si positif : le budget était trop haut, on rend de l'argent (Crédit copro)
            // Si négatif : le budget était trop bas, il doit de l'argent (Débit copro)
            $resultatRegularisation = round($totalAppele - $quotePartReelle, 2);

            $bilan[$copro->getId()] = [
                'coproprietaire' => $copro,
                'tantiemes' => $tantiemesCopro,
                'totalAppele' => $totalAppele,
                'quotePartReelle' => $quotePartReelle,
                'resultat' => $resultatRegularisation
            ];
        }

        return [
            'totalChargesGlobales' => $totalChargesReelles,
            'details' => $bilan
        ];
    }

    /**
     * Génère l'opération de régularisation et les écritures associées en BDD.
     */
    public function genererRegularisation(Exercice $exercice): void
    {
        $simulation = $this->simulerRegularisation($exercice);

        if (empty($simulation['details'])) {
            return;
        }

        // 1. Créer l'opération principale
        $operation = new Operation();
        $operation->setDate(new \DateTimeImmutable());
        $operation->setLibelle('Régularisation des charges - ' . $exercice->getNom());
        $operation->setType(OperationType::REGULARISATION);
        $operation->setStatut(OperationStatut::VALIDE);
        $this->em->persist($operation);

        // Récupérer le compte 120000 (Résultat de l'exercice) pour la contrepartie globale
        $compteResultat = $this->em->getRepository(\App\Entity\Compte::class)->findOneBy(['numero' => '120000']);

        foreach ($simulation['details'] as $detail) {
            $copro = $detail['coproprietaire'];
            $montant = $detail['resultat'];

            if ($montant == 0) {
                continue;
            }

            // Écriture pour le compte du copropriétaire (Son compte tiers ex: 450001)
            $ecritureCopro = new Ecriture();
            $ecritureCopro->setOperation($operation);
            $ecritureCopro->setExercice($exercice);
            $ecritureCopro->setCompte($copro->getCompte()); // Relation vers l'entité Compte
            $ecritureCopro->setCoproprietaire($copro);
            $ecritureCopro->setDate(new \DateTimeImmutable());

            // Écriture de contrepartie (Compte de résultat de l'exercice)
            $ecritureContrepartie = new Ecriture();
            $ecritureContrepartie->setOperation($operation);
            $ecritureContrepartie->setExercice($exercice);
            $ecritureContrepartie->setCompte($compteResultat);
            $ecritureContrepartie->setDate(new \DateTimeImmutable());

            if ($montant > 0) {
                // Trop-perçu : On CRÉDITE le copropriétaire (le syndic lui doit de l'argent)
                $ecritureCopro->setCredit($montant);
                $ecritureCopro->setDebit(0);

                // Contrepartie au DÉBIT du compte de résultat
                $ecritureContrepartie->setDebit($montant);
                $ecritureContrepartie->setCredit(0);
            } else {
                // Moins-perçu : On DÉBITE le copropriétaire (il doit de l'argent au syndic)
                $valeurAbsolue = abs($montant);
                $ecritureCopro->setDebit($valeurAbsolue);
                $ecritureCopro->setCredit(0);

                // Contrepartie au CRÉDIT du compte de résultat
                $ecritureContrepartie->setCredit($valeurAbsolue);
                $ecritureContrepartie->setDebit(0);
            }

            $this->em->persist($ecritureCopro);
            $this->em->persist($ecritureContrepartie);
        }

        $this->em->flush();
    }
}
