# ComptaSyndic V4

## Présentation

ComptaSyndic V4 est une application Symfony destinée à assurer la gestion
comptable d'une copropriété.

L'objectif est de disposer d'une comptabilité conforme aux règles de la
copropriété tout en restant simple d'utilisation pour un syndic bénévole.

L'application est construite autour d'un principe fondamental :

> Toute écriture comptable est générée par les services métier.
> Aucune écriture n'est créée manuellement.

---

# Architecture générale

Le projet est organisé autour de quatre couches.

```
Controller
        │
        ▼
Services métier
        │
        ▼
ComptabiliteService
        │
        ▼
Doctrine / Base de données
```

Le contrôleur ne contient jamais de logique comptable.

Il récupère les données du formulaire puis appelle un service.

Toute la logique métier est regroupée dans les services.

---

# Le rôle de ComptabiliteService

ComptabiliteService constitue le cœur du logiciel.

Tous les autres services passent obligatoirement par lui.

Il est responsable de :

- création des opérations
- création des écritures
- équilibre débit / crédit
- rattachement à l'exercice
- rattachement aux copropriétaires

Aucun autre service ne crée directement une écriture comptable.

---

# Les principaux services

## GenerationFactureFournisseurService

Responsabilité :

Création des écritures correspondant à une facture fournisseur.

Produit :

- opération CHARGE
- débit compte de charge
- crédit fournisseur
- génération des répartitions

---

## ReglementFactureFournisseurService

Responsabilité :

Paiement d'une facture fournisseur.

Produit :

- opération PAIEMENT_FOURNISSEUR
- débit fournisseur
- crédit banque

---

## GenerationPaiementService

Responsabilité :

Paiement d'un copropriétaire.

Produit :

- débit banque
- crédit copropriétaire

---

## GenerationAppelFondService

Responsabilité :

Création d'un appel de fonds.

Produit :

- débit copropriétaires
- crédit produits

---

## RepartitionService

Responsabilité :

Répartition automatique des charges entre les lots.

Modes disponibles :

- tantièmes
- égalitaire

---

## ClotureExerciceService

Responsabilité :

Clôture comptable d'un exercice.

Fonctions :

- vérifications
- régularisations
- clôture des comptes de gestion
- génération des à-nouveaux
- bascule vers l'exercice suivant

---

# Cycle comptable

Facture fournisseur

↓

Paiement fournisseur

↓

Répartition

↓

Paiement copropriétaires

↓

Régularisations

↓

Clôture

↓

A-nouveaux

↓

Nouvel exercice

---

# Principe général

Une opération comptable peut produire plusieurs écritures.

Une écriture appartient toujours :

- à une opération
- à un exercice
- à un compte

et éventuellement :

- à un copropriétaire.

---

# Types d'opérations

CHARGE

Facture fournisseur.

PAIEMENT_FOURNISSEUR

Paiement d'un fournisseur.

APPEL_FONDS

Appel de provisions.

PAIEMENT

Paiement d'un copropriétaire.

REGULARISATION

Répartition définitive des charges.

CLOTURE

Clôture des comptes de gestion.

APPROBATION_COMPTES

Décision de l'Assemblée Générale.

A_NOUVEAU

Reprise des soldes de l'exercice précédent.

---

# Principes de développement

Les contrôleurs restent les plus simples possible.

Les services contiennent la logique métier.

Les entités représentent uniquement les données.

Les écritures sont toujours générées automatiquement.

Chaque nouvelle fonctionnalité doit être développée sous forme de service.

Chaque opération comptable doit être traçable.

Chaque évolution importante est documentée dans le dossier /docs.