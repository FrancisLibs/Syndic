# ComptaSyndic V4

## Présentation

ComptaSyndic V4 est une application de comptabilité destinée aux syndics bénévoles de copropriété.

Le projet est né d'un besoin concret : disposer d'un logiciel simple, fiable et moderne permettant de gérer intégralement la comptabilité d'une copropriété sans recourir à une solution professionnelle coûteuse ou surdimensionnée.

L'objectif n'est pas uniquement de produire une comptabilité conforme, mais d'accompagner le syndic dans l'ensemble de son travail quotidien.

L'application automatise les traitements comptables à partir des opérations métier réalisées par l'utilisateur.

Ainsi, le syndic saisit uniquement :

- les budgets,
- les appels de fonds,
- les factures fournisseurs,
- les paiements des copropriétaires,
- les relevés des compteurs d'eau.

Toutes les écritures comptables sont générées automatiquement.

---

# Philosophie du projet

ComptaSyndic V4 repose sur un principe simple :

> La comptabilité est la conséquence des opérations de gestion.

L'utilisateur ne travaille jamais directement dans un journal comptable.

Il travaille sur des objets métier.

Par exemple :

Budget

↓

Appel de fonds

↓

Paiement

↓

Facture fournisseur

↓

Répartition

↓

Régularisation

↓

Clôture

↓

Nouvel exercice

Chaque étape produit automatiquement les écritures nécessaires.

Cette approche présente plusieurs avantages :

- diminution des erreurs de saisie ;
- cohérence permanente de la comptabilité ;
- simplicité d'utilisation ;
- traçabilité complète des traitements.

---

# Fonctionnalités principales

Le logiciel prend en charge :

## Gestion générale

- copropriétés
- lots
- copropriétaires
- fournisseurs
- comptes comptables
- types de charges

## Comptabilité

- budgets
- appels de fonds
- paiements copropriétaires
- factures fournisseurs
- règlements fournisseurs
- journal
- grand livre
- balance

## Répartitions

- répartition aux tantièmes
- répartition égalitaire
- contrôles automatiques
- validation des répartitions

## Module Eau

- compteur général
- compteurs individuels
- relevés
- calcul des consommations
- prix du m³
- répartition des charges d'eau
- validation

## Clôture

- assistant de clôture
- contrôles préalables
- régularisations
- clôture des comptes de gestion
- génération des à-nouveaux
- création automatique du nouvel exercice

---

# Technologies

Le projet est développé avec :

- PHP 8
- Symfony 6.4
- Doctrine ORM
- MySQL
- Twig
- Bootstrap 5

---

# Principes de développement

Plusieurs règles guident le développement.

## Automatisation

Une opération métier produit automatiquement sa comptabilité.

L'utilisateur ne crée jamais manuellement les écritures.

---

## Contrôle

Chaque traitement important possède son écran de validation.

Exemples :

- validation des répartitions
- validation des régularisations (à venir)
- validation de la clôture (à venir)

---

## Traçabilité

Toutes les écritures comptables sont générées à partir d'une opération identifiable.

Il est toujours possible de remonter de l'écriture jusqu'à l'action utilisateur qui l'a produite.

---

## Fiabilité

Chaque génération est vérifiée.

Les traitements utilisent systématiquement des contrôles métier :

- équilibre comptable
- vérification des répartitions
- contrôle des consommations d'eau
- contrôle des régularisations
- contrôle des arrondis

---

# Le module Eau

Le module Eau constitue une particularité de ComptaSyndic V4.

Les factures d'eau sont comptabilisées immédiatement mais ne sont pas réparties.

La répartition intervient uniquement après la saisie des relevés de compteurs.

Les consommations communes sont réparties aux tantièmes généraux de la copropriété.

Cette méthode correspond au choix retenu pour cette copropriété.

---

# Clôture d'exercice

La clôture est entièrement guidée.

Elle comprend notamment :

- les contrôles préalables ;
- les régularisations ;
- la clôture des comptes de gestion ;
- le calcul du résultat ;
- la génération des à-nouveaux ;
- la création du nouvel exercice.

Le compte 489000 est utilisé comme compte de transit pour répartir le résultat entre les copropriétaires.

---

# Documentation

La documentation complète est disponible dans le dossier `docs`.

Elle comprend :

- Architecture
- Modèle comptable
- Fonctionnement
- Module Eau
- Clôture d'exercice
- Guide développeur
- Backlog
- Changelog

---

# État du projet

Le projet est actuellement en phase de stabilisation.

L'ensemble du cycle comptable est opérationnel :

✓ Budget

✓ Appels de fonds

✓ Paiements

✓ Factures fournisseurs

✓ Répartitions

✓ Module Eau

✓ Régularisations

✓ Clôture

✓ À nouveaux

Les développements futurs porteront principalement sur :

- l'ergonomie ;
- les assistants ;
- les écrans de validation ;
- l'amélioration du confort d'utilisation.
