<?php

namespace App\Controller;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Enum\OperationType;
use App\Repository\CompteRepository;
use App\Repository\CoproprietaireRepository;
use App\Repository\ExerciceRepository;
use App\Repository\OperationRepository;
use App\Service\ComptabiliteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestANouveauController extends AbstractController
{
    #[Route(
        '/test/a-nouveaux-2023',
        name: 'app_test_a_nouveaux_2023',
        methods: ['GET']
    )]
    public function generer(
        ExerciceRepository $exerciceRepository,
        CoproprietaireRepository $coproprietaireRepository,
        CompteRepository $compteRepository,
        OperationRepository $operationRepository,
        ComptabiliteService $comptabiliteService,
        EntityManagerInterface $entityManager,
    ): Response {
        /*
         * À adapter uniquement si ton exercice ne porte pas exactement
         * le nom « Exercice 2023 ».
         */
        $exercice = $exerciceRepository->findOneBy([
            'nom' => 'Exercice 2023',
        ]);

        if (!$exercice instanceof Exercice) {
            throw $this->createNotFoundException(
                'L’exercice « Exercice 2023 » est introuvable.'
            );
        }

        /*
         * Sécurité : évite de générer deux fois les à-nouveaux.
         *
         * Comme Operation ne possède pas directement d’exercice,
         * cette méthode repository doit rechercher via les écritures.
         */
        if ($operationRepository->existePourExerciceEtType(
            $exercice,
            OperationType::A_NOUVEAU
        )) {
            return new Response(
                'Les à-nouveaux existent déjà pour ' . $exercice->getNom() . '.'
            );
        }

        $compteBanque = $compteRepository->findOneBy([
            'numero' => '512000',
        ]);

        $compteAvanceTresorerie = $compteRepository->findOneBy([
            'numero' => '103100',
        ]);

        /*
         * Compte utilisé uniquement pour absorber les quelques centimes
         * provenant des arrondis du tableau Excel.
         *
         * Adapte le numéro si ton plan comptable utilise un autre compte.
         */
        $compteEcartArrondi = $compteRepository->findOneBy([
            'numero' => '658000',
        ]);

        if ($compteBanque === null) {
            throw new \RuntimeException(
                'Le compte bancaire 512000 est introuvable.'
            );
        }

        if ($compteAvanceTresorerie === null) {
            throw new \RuntimeException(
                'Le compte 103100 – Avance de trésorerie est introuvable.'
            );
        }

        if ($compteEcartArrondi === null) {
            throw new \RuntimeException(
                'Le compte 658000 utilisé pour l’écart d’arrondi est introuvable.'
            );
        }

        $operation = $comptabiliteService->creerOperation(
            new \DateTimeImmutable('2023-01-01'),
            'À-nouveaux au 1er janvier 2023',
            OperationType::A_NOUVEAU,
            'AN-2023'
        );

        /*
         * Banque : solde débiteur.
         */
        $comptabiliteService->creerDebit(
            operation: $operation,
            compte: $compteBanque,
            montant: 1522.90,
            exercice: $exercice
        );

        /*
         * Avance permanente de trésorerie :
         * dette de la copropriété envers les copropriétaires.
         */
        $comptabiliteService->creerCredit(
            operation: $operation,
            compte: $compteAvanceTresorerie,
            montant: 1000.00,
            exercice: $exercice
        );

        /*
         * Soldes individuels.
         *
         * Montant positif  : copropriétaire créditeur.
         * Montant négatif  : copropriétaire débiteur.
         */
        $soldes = [
            'Fuhrmann'          => 246.66,
            'Feta'              => 32.06,
            'Libs'              => -558.59,
            'Pirot'             => 336.43,
            'Vieville Jacques'  => 331.91,
            'Vieville Laurence' => 199.83,
            'Jouaville'         => -65.37,
        ];

        foreach ($soldes as $nom => $solde) {
            $coproprietaire = $this->trouverCoproprietaire(
                $coproprietaireRepository,
                $nom
            );

            $compte = $coproprietaire->getCompte();

            if ($compte === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Aucun compte comptable n’est associé à %s.',
                        $nom
                    )
                );
            }

            if ($solde > 0) {
                $comptabiliteService->creerCredit(
                    operation: $operation,
                    compte: $compte,
                    montant: $solde,
                    exercice: $exercice,
                    coproprietaire: $coproprietaire
                );
            } elseif ($solde < 0) {
                $comptabiliteService->creerDebit(
                    operation: $operation,
                    compte: $compte,
                    montant: abs($solde),
                    exercice: $exercice,
                    coproprietaire: $coproprietaire
                );
            }
        }

        /*
         * Les montants individuels arrondis produisent un écart de 0,03 € :
         *
         * Débits avant ajustement  : 2 146,86 €
         * Crédits                  : 2 146,89 €
         */
        $comptabiliteService->creerDebit(
            operation: $operation,
            compte: $compteEcartArrondi,
            montant: 0.03,
            exercice: $exercice
        );

        $entityManager->flush();

        return new Response(
            sprintf(
                'À-nouveaux générés pour %s. Débits : 2 146,89 € — Crédits : 2 146,89 €.',
                $exercice->getNom()
            )
        );
    }

    private function trouverCoproprietaire(
        CoproprietaireRepository $repository,
        string $nom
    ): Coproprietaire {
        /*
         * Cette recherche suppose que l’entité possède une propriété "nom".
         * Nous l’adapterons si elle utilise nom/prenom séparément.
         */
        $coproprietaire = $repository->findOneBy([
            'nom' => $nom,
        ]);

        if (!$coproprietaire instanceof Coproprietaire) {
            throw new \RuntimeException(
                sprintf(
                    'Le copropriétaire « %s » est introuvable.',
                    $nom
                )
            );
        }

        return $coproprietaire;
    }
}
