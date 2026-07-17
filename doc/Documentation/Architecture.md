# Architecture

Version : 1.0

---

# 1. Objectif

Ce document décrit l'architecture générale de ComptaSyndic V4.

Il présente les principaux composants de l'application ainsi que leurs responsabilités.

L'objectif est de permettre à un développeur de comprendre rapidement l'organisation du projet avant d'intervenir sur le code.

---

# 2. Technologies

Le projet repose sur les technologies suivantes :

- PHP 8
- Symfony 6.4
- Doctrine ORM
- MySQL
- Twig
- Bootstrap 5

Le développement suit l'architecture MVC proposée par Symfony.

---

# 3. Architecture générale

Le projet est organisé autour des composants suivants :

```
Controller
      │
      ▼
Service métier
      │
      ▼
Repository
      │
      ▼
Doctrine ORM
      │
      ▼
MySQL
```

Les contrôleurs restent volontairement très légers.

Toute la logique métier est concentrée dans les services.

---

# 4. Les contrôleurs

Les contrôleurs ont pour unique rôle de :

- recevoir les requêtes HTTP ;
- construire les formulaires ;
- appeler les services métier ;
- transmettre les données aux vues.

Ils ne doivent contenir aucune logique métier importante.

Les traitements complexes sont systématiquement délégués aux services.

---

# 5. Les services

Les services constituent le cœur de l'application.

Ils réalisent tous les traitements métier.

Exemples :

- génération des appels de fonds ;
- génération comptable ;
- affectation des paiements ;
- calcul des répartitions ;
- calcul des consommations d'eau ;
- génération des régularisations ;
- clôture d'exercice.

Cette organisation facilite :

- les tests ;
- la maintenance ;
- la réutilisation du code.

---

# 6. Les repositories

Les repositories assurent exclusivement l'accès aux données.

Ils contiennent :

- les recherches ;
- les agrégations ;
- les requêtes DQL.

Ils ne réalisent jamais de traitements métier.

---

# 7. Les entités

Chaque entité représente un objet métier.

Les principales entités sont :

## Gestion

- Copropriete
- Lot
- Coproprietaire
- Fournisseur

## Comptabilité

- Exercice
- Budget
- LigneBudget
- AppelFond
- LigneAppelFond
- FactureFournisseur
- Paiement
- Operation
- Ecriture
- Repartition

## Eau

- CompteurEau
- ReleveCompteur

Les entités décrivent uniquement les données.

Les traitements sont réalisés par les services.

---

# 8. Les DTO

Les DTO (Data Transfer Objects) servent à transporter des données calculées.

Ils évitent d'ajouter des propriétés temporaires aux entités.

Exemples :

- CalculEau
- CalculEauLot
- ConsommationImmeuble
- ConsommationCompteur

Les DTO ne sont jamais persistés.

---

# 9. Les énumérations

Le projet utilise plusieurs énumérations afin d'éviter les chaînes de caractères dispersées dans le code.

Exemples :

- OperationType
- OperationStatut
- CompteType
- ModeRepartition
- ExerciceStatut

Cette approche améliore la lisibilité et réduit les erreurs.

---

# 10. Génération comptable

Toutes les écritures comptables sont produites par les services.

Le service central est :

ComptabiliteService

Il fournit notamment les méthodes permettant de :

- créer une opération ;
- créer une écriture au débit ;
- créer une écriture au crédit ;
- enregistrer une opération complète.

Tous les autres services s'appuient sur lui.

Ainsi, les règles comptables sont centralisées.

---

# 11. Les modules

L'application est organisée par domaines fonctionnels.

## Budgets

Gestion des budgets prévisionnels.

---

## Appels de fonds

Génération des appels.

Répartition automatique.

Comptabilisation.

---

## Paiements

Paiements copropriétaires.

Affectation automatique.

Suivi des soldes.

---

## Fournisseurs

Factures.

Paiements.

Comptabilisation.

---

## Répartitions

Calcul des charges.

Validation.

Contrôles.

---

## Eau

Compteurs.

Relevés.

Calculs.

Répartition.

---

## Clôture

Contrôles.

Régularisations.

Résultat.

A nouveaux.

Nouvel exercice.

---

# 12. Principe de génération

Toutes les générations suivent le même modèle.

```
Contrôles

        │

        ▼

Calcul

        │

        ▼

Création des objets

        │

        ▼

Comptabilisation

        │

        ▼

Validation
```

Cette structure est commune à la majorité des traitements.

Elle facilite la compréhension du code.

---

# 13. Gestion des arrondis

Les calculs financiers sont systématiquement arrondis à deux décimales.

Lorsque plusieurs répartitions sont générées :

- les lignes intermédiaires sont arrondies individuellement ;
- l'écart éventuel est reporté sur la dernière ligne.

Cette méthode garantit :

Somme des lignes = montant d'origine.

---

# 14. Gestion des contrôles

Avant chaque traitement important, le logiciel effectue des contrôles métier.

Exemples :

- existence d'un exercice actif ;
- équilibre des opérations ;
- budget verrouillé ;
- répartitions cohérentes ;
- consommation d'eau valide.

Les traitements sont interrompus dès qu'une anomalie est détectée.

---

# 15. Principes de développement

Le projet respecte les principes suivants.

## Responsabilité unique

Chaque service réalise un traitement précis.

Exemple :

GenerationAppelFondService

ne génère que les écritures d'appel de fonds.

---

## Réutilisation

Les traitements communs sont factorisés.

Exemple :

ComptabiliteService.

---

## Traçabilité

Toute écriture est rattachée à une opération.

Toute opération est rattachée à un exercice.

---

## Lisibilité

Le code privilégie :

- des méthodes courtes ;
- des noms explicites ;
- une séparation claire entre les couches.

---

# 16. Évolutions

L'architecture a été pensée pour permettre l'ajout de nouveaux modules.

Un nouveau traitement suit généralement le schéma suivant :

- nouvelle entité (si nécessaire) ;
- repository ;
- service métier ;
- contrôleur ;
- vues Twig.

Le reste de l'application n'a généralement pas besoin d'être modifié.

---

# 17. Conclusion

ComptaSyndic V4 repose sur une architecture volontairement simple.

Les contrôleurs pilotent les traitements.

Les services contiennent la logique métier.

Les repositories assurent l'accès aux données.

Les entités représentent les objets métier.

Cette organisation permet de faire évoluer facilement l'application tout en conservant une séparation claire des responsabilités.