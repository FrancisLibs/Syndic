# Architecture

## Projet

Application de gestion comptable pour syndic bénévole.

---

# Philosophie

Le logiciel doit appliquer automatiquement le bon sens comptable d'un syndic bénévole expérimenté, tout en rendant chaque décision compréhensible et vérifiable.

Chaque chiffre affiché doit pouvoir être expliqué.

Le logiciel ne remplace pas le syndic.
Il l'accompagne.

---

# Principes

## Une seule source de vérité

Les écritures comptables sont la référence.

Tous les calculs en découlent.

Aucune donnée calculée ne doit être dupliquée inutilement.

---

## Calcul ≠ Génération

Un calcul ne modifie jamais la base de données.

Une génération crée des écritures comptables.

---

## Contrôle avant action

Toute génération doit être précédée d'un contrôle utilisateur.

---

## Les conséquences sont persistées.

Les calculs ne le sont pas.

Exemples :

✔ régularisations générées

✔ à-nouveaux générés

✔ exercice clôturé

Mais jamais :

✘ calcul des soldes effectué

✘ validation consultée

---

# Architecture

Entity
    ↓
Repository
    ↓
Service
    ↓
DTO
    ↓
Controller
    ↓
Twig

# Workflow de clôture

1. Vérifications préalables

2. Calcul des régularisations

3. Validation des régularisations

4. Génération des régularisations

5. Calcul des soldes reportables

6. Validation des soldes reportables

7. Génération des à-nouveaux

8. Validation des à-nouveaux

9. Clôture de l'exercice

10. Activation du nouvel exercice


# Repository

- accès aux données
- requêtes DQL
- aucun métier

Service

- règles métier
- calculs
- décisions

DTO

- transport des données
- état du workflow

Controller

- orchestration

Twig

- affichage uniquement



# EtatCloture

- état général
- informations de progression
- données nécessaires à l'affichage

SoldeReportable

- compte
- copropriétaire
- débit
- crédit

Regularisation (à créer)

- copropriétaire
- montant

# Sprint 0

✔ Architecture

Sprint 1

Assistant de clôture

Sprint 2

Vérifications

Sprint 3

Régularisations

Sprint 4

Soldes reportables

Sprint 5

À-nouveaux

Sprint 6

Clôture

Sprint 7

Dossier de clôture

# Règle d'or

Avant d'ajouter une fonctionnalité, toujours se poser les questions suivantes :

- Quel besoin métier satisfait-elle ?
- À quelle étape du workflow appartient-elle ?
- Produit-elle un calcul ou une écriture comptable ?
- Comment l'utilisateur pourra-t-il comprendre le résultat ?
- Comment le prochain syndic pourra-t-il vérifier ce résultat ?