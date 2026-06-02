<?php

namespace App\DataFixtures;

use App\Entity\Compte;
use App\Entity\Coproprietaire;
use App\Entity\Copropriete;
use App\Entity\Exercice;
use App\Entity\Lot;
use App\Enum\CompteType;
use App\Enum\ExerciceStatut;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ComptaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $comptes = [
            // --- COMPTES DE BILAN (Classe 4 et 5) ---
            // Indispensable pour savoir QUI doit QUOI
            ['450', 'Copropriétaires', 'tiers'],

            // Indispensable pour savoir où va l'ARGENT
            ['512', 'Banque', 'banque'],

            // --- COMPTES DE CHARGES (Classe 6) ---
            ['601', 'Électricité', 'charge'],
            ['602', 'Eau', 'charge'],
            ['603', 'Ménage', 'charge'],
            ['604', 'Assurance', 'charge'],
            ['605', 'Frais syndic', 'charge'],
            ['606', 'Frais postaux', 'charge'],
            ['608', 'Travaux', 'charge'],
            ['609', 'Divers', 'charge'],
        ];

        $coproprietaires = [
            ['Libs'],
            ['Jouaville'],
            ['Pirot'],
            ['Vieville'],
            ['Feta'],
            ['Robichon & Ferre'],
        ];

        $lots = [
            ['1', 'RDC gauche', 1063, 1, 6],
            ['2', 'RDC droit', 1225, 1, 5],
            ['3', '1er étage gauche', 997, 1, 1],
            ['4', '1er étage droit', 1588, 1, 3],
            ['5', '2e étage gauche', 1014, 1, 1],
            ['6', '2e étage droit', 1631, 1, 4],
            ['7', '3e étage gauche', 961, 1, 4],
            ['8', '3e étage droit', 1521, 1, 2],
        ];

        $coproprietes = [
            [
                '14 RUE NEUVE',
                '14, rue Neuve',
                67200,
                'Strasbourg',
                10000,
            ]
        ];

        $exercices = [
            [
                '2025',
                01 / 01 / 2025,
                01 / 01 / 2025,
                'ouvert'
            ]
        ];

        $coproprietesEntities = [];

        foreach ($coproprietes as [$nom, $adresse, $codePostal, $localite, $tantiemes]) {
            $copropriete = new Copropriete();
            $copropriete->setNom($nom);
            $copropriete->setAdresse($adresse);
            $copropriete->setCodePostal($codePostal);
            $copropriete->setLocalite($localite);
            $copropriete->setTantiemesBase($tantiemes);

            $manager->persist($copropriete);
            $coproprietesEntities[] = $copropriete;
        }

        $coproprietairesEntities = [];

        foreach ($coproprietaires as [$nom]) {
            $coproprietaire = new Coproprietaire();
            $coproprietaire->setNom($nom);

            $manager->persist($coproprietaire);
            $coproprietairesEntities[] = $coproprietaire;
        }

        foreach ($lots as [$reference, $designation, $tantiemes, $coproprieteIndex, $coproprietaireIndex]) {
            $lot = new Lot();
            $lot->setReference($reference);
            $lot->setDesignation($designation);
            $lot->setTantiemes($tantiemes);

            $lot->setCopropriete($coproprietesEntities[$coproprieteIndex - 1]);
            $lot->setCoproprietaire($coproprietairesEntities[$coproprietaireIndex - 1]);

            $manager->persist($lot);
        }



        foreach ($comptes as [$numero, $libelle, $typeStr]) {
            $compte = new Compte();
            $compte->setNumero($numero);
            $compte->setLibelle($libelle);

            // On transforme la string en valeur d'Enum
            // Adapté selon les noms dans ton fichier CompteType.php
            $typeEnum = CompteType::from($typeStr);
            $compte->setType($typeEnum);

            $manager->persist($compte);
        }

        $exercice = new Exercice();
        $exercice->setnom('2025');
        $exercice->setDateDebut(new \DateTimeImmutable('2025-01-01'));
        $exercice->setDateFin(new \DateTimeImmutable('2025-12-31'));
        $exercice->setStatut(ExerciceStatut::OUVERT);

        $manager->persist($exercice);

        $manager->flush();
    }
}
