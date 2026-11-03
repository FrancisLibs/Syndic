<?php

namespace App\Service;

use App\Entity\AppelFond;
use App\Entity\Budget;
use App\Entity\LigneAppelFond;
use App\Repository\AppelFondRepository;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class GenerateurAppelFondService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LotRepository $lotRepository,
        private AppelFondRepository $appelFondRepository,
    ) {}

    public function generer(
        Budget $budget,
        \DateTimeImmutable $dateAppel,
        \DateTimeImmutable $dateReglement,
    ): AppelFond {

        // ==========================================
        // 1. Vérifications
        // ==========================================

        if ($dateReglement < $dateAppel) {
            throw new \LogicException(
                'La date d’échéance ne peut pas précéder la date de l’appel.'
            );
        }

        $nbAppels = $budget->getNbAppels();

        if ($nbAppels < 1) {
            throw new \LogicException(
                'Le nombre d’appels doit être supérieur à zéro.'
            );
        }

        // On interroge les appels existants.
        // Ne pas se fier uniquement à la collection Doctrine,
        // qui peut ne pas être synchronisée en mémoire.

        $appelsExistants = $this->appelFondRepository->findBy(
            ['budget' => $budget],
            ['dateAppel' => 'ASC']
        );

        $nombreAppelsGeneres = count($appelsExistants);

        if ($nombreAppelsGeneres >= $nbAppels) {
            throw new \LogicException(
                'Tous les appels prévus pour ce budget ont déjà été générés.'
            );
        }

        $numeroAppel = $nombreAppelsGeneres + 1;

        // ==========================================
        // 2. Calcul du montant du budget
        // ==========================================

        $montantTotalBudget = round(
            $budget->getTotal(),
            2
        );

        if ($montantTotalBudget <= 0) {
            throw new \LogicException(
                'Le montant total du budget doit être positif.'
            );
        }

        // ==========================================
        // 3. Calcul du montant du prochain appel
        // ==========================================

        $montantDejaAppele = 0.0;

        foreach ($appelsExistants as $appel) {
            $montantDejaAppele +=
                (float) $appel->getMontantTotal();
        }

        $montantDejaAppele = round(
            $montantDejaAppele,
            2
        );

        if ($numeroAppel === $nbAppels) {

            // Dernier appel : on prend le solde du budget
            // pour absorber les éventuels écarts d'arrondi.

            $montantAppel = round(
                $montantTotalBudget - $montantDejaAppele,
                2
            );
        } else {

            $montantAppel = round(
                $montantTotalBudget / $nbAppels,
                2
            );
        }

        if ($montantAppel <= 0) {
            throw new \LogicException(
                'Le montant du prochain appel est invalide.'
            );
        }

        // ==========================================
        // 4. Création de l'appel
        // ==========================================

        $appelFond = new AppelFond();

        $appelFond
            ->setBudget($budget)
            ->setLibelle(
                sprintf(
                    'Appel de fonds %d/%d - %s',
                    $numeroAppel,
                    $nbAppels,
                    $budget->getLibelle()
                )
            )
            ->setDateAppel($dateAppel)
            ->setDateReglement($dateReglement)
            ->setMontantTotal($montantAppel);

        // ==========================================
        // 5. Numérotation automatique
        // ==========================================

        $annee = (int) $dateAppel->format('Y');

        $numero = $this->appelFondRepository
            ->getProchainNumero($annee);

        $appelFond->setNumero($numero);

        // ==========================================
        // 6. Recherche des lots
        // ==========================================

        $copropriete = $budget->getCopropriete();

        $lots = $this->lotRepository->findBy([
            'copropriete' => $copropriete,
        ]);

        if ($lots === []) {
            throw new \LogicException(
                'Aucun lot trouvé pour cette copropriété.'
            );
        }

        // ==========================================
        // 7. Total des tantièmes
        // ==========================================

        $totalTantiemes = 0;

        foreach ($lots as $lot) {
            $totalTantiemes += $lot->getTantiemes();
        }

        if ($totalTantiemes <= 0) {
            throw new \LogicException(
                'Le total des tantièmes est invalide.'
            );
        }

        // ==========================================
        // 8. Préparation des répartitions
        // ==========================================

        $lignesARepartir = [];

        foreach ($lots as $lot) {

            $tantiemesLot = $lot->getTantiemes();

            $montantLot =
                ($montantAppel * $tantiemesLot)
                / $totalTantiemes;

            foreach ($lot->getLotCoproprietaires() as $lotCoproprietaire) {

                $dateDebut = $lotCoproprietaire->getDateDebut();
                $dateFin = $lotCoproprietaire->getDateFin();

                if ($dateDebut > $dateAppel) {
                    continue;
                }

                if ($dateFin && $dateFin < $dateAppel) {
                    continue;
                }

                $pourcentage = (float)
                $lotCoproprietaire->getPourcentage();

                $montantBrut =
                    $montantLot * $pourcentage / 100;

                $lignesARepartir[] = [
                    'lot' => $lot,
                    'coproprietaire' =>
                    $lotCoproprietaire->getCoproprietaire(),
                    'pourcentage' => $pourcentage,
                    'montantBrut' => $montantBrut,
                ];
            }
        }

        if ($lignesARepartir === []) {
            throw new \LogicException(
                'Aucun copropriétaire actif trouvé à la date de l’appel.'
            );
        }

        // ==========================================
        // 9. Répartition avec gestion des arrondis
        // ==========================================

        $totalGenere = 0.0;
        $dernierIndex = count($lignesARepartir) - 1;

        foreach ($lignesARepartir as $index => $donnees) {

            if ($index === $dernierIndex) {

                $montant = round(
                    $montantAppel - $totalGenere,
                    2
                );
            } else {

                $montant = round(
                    $donnees['montantBrut'],
                    2
                );

                $totalGenere += $montant;
            }

            $ligne = new LigneAppelFond();

            $ligne
                ->setMontantRegle('0.00')
                ->setSoldee(false)
                ->setAppelFond($appelFond)
                ->setLot($donnees['lot'])
                ->setCoproprietaire(
                    $donnees['coproprietaire']
                )
                ->setPourcentage(
                    $donnees['pourcentage']
                )
                ->setMontant(
                    number_format(
                        $montant,
                        2,
                        '.',
                        ''
                    )
                );

            $appelFond->addLigneAppelFond($ligne);

            $this->entityManager->persist($ligne);
        }

        // ==========================================
        // 10. Verrouillage du budget
        // ==========================================

        // Le budget devient non modifiable dès le
        // premier appel, mais les appels suivants
        // restent autorisés.

        $budget->setVerrouille(true);

        // ==========================================
        // 11. Enregistrement
        // ==========================================

        $this->entityManager->persist($appelFond);

        $this->entityManager->flush();

        return $appelFond;
    }
}
