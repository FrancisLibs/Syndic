# Règles métier

## ComptaSyndic V4

Ce document recense toutes les règles métier qui gouvernent le fonctionnement
de ComptaSyndic V4.

Ces règles constituent la référence fonctionnelle de l'application.

En cas de doute, ce document prévaut sur l'implémentation.

---

# 1. Généralités

## 1.1 Une opération produit des écritures

Une opération comptable peut produire une ou plusieurs écritures.

Les écritures sont toujours équilibrées.

Total Débit = Total Crédit

Une écriture ne doit jamais être créée directement.

Toutes les écritures sont générées par ComptabiliteService.

---

## 1.2 Exercice

Toute opération appartient à un exercice.

Toute écriture appartient au même exercice que son opération.

Une opération ne peut jamais concerner plusieurs exercices.

---

## 1.3 Exercice clôturé

Un exercice clôturé est figé.

Il est interdit de :

- créer une facture
- enregistrer un paiement
- créer un appel de fonds
- modifier une répartition
- ajouter une écriture

Les seules consultations restent autorisées.

---

# 2. Factures fournisseurs

Une facture fournisseur génère automatiquement :

- une opération CHARGE
- un débit du compte de charge
- un crédit du fournisseur

Si un copropriétaire a avancé la dépense :

- le crédit est porté sur son compte.

La répartition est générée automatiquement.

---

# 3. Paiement fournisseur

Le règlement d'une facture :

- débite le fournisseur (ou le copropriétaire avanceur)
- crédite la banque.

Une facture soldée ne peut plus être réglée.

Le règlement ne peut être antérieur à la facture.

---

# 4. Appels de fonds

Les appels de fonds sont générés à partir d'un budget.

Ils produisent :

Débit copropriétaires

Crédit produits.

---

# 5. Paiements copropriétaires

Le paiement d'un copropriétaire produit :

Débit banque

Crédit compte copropriétaire.

Les affectations permettent ensuite de rapprocher le paiement des appels.

---

# 6. Répartition des charges

Une répartition appartient toujours à :

- une écriture
- un exercice
- un lot
- un copropriétaire

Modes disponibles :

- tantièmes
- égalitaire

Le total réparti doit toujours être égal au montant comptabilisé.

---

# 7. Eau

Le calcul du prix du m³ est :

Montant total des factures d'eau
÷
Consommation générale

L'écart entre :

compteur général

et

somme des compteurs individuels

est réparti selon les tantièmes généraux.

Une seule alimentation générale est gérée.

Chaque lot possède au maximum un compteur individuel.

---

# 8. Régularisations

Les régularisations comparent :

Charges réelles

et

Appels de fonds.

Les différences sont calculées pour chaque copropriétaire.

Aucune écriture n'est générée tant que les comptes ne sont pas approuvés.

---

# 9. Compte 489000

Le compte 489000 est utilisé comme compte d'attente.

Après approbation des comptes :

les soldes sont transférés vers les comptes copropriétaires.

---

# 10. Clôture

La clôture comprend :

- vérifications
- génération des régularisations
- clôture des comptes de gestion
- génération des à-nouveaux
- activation de l'exercice suivant

Les comptes de charges et produits sont soldés.

Les comptes de bilan sont repris.

---

# 11. À-nouveaux

Les à-nouveaux ne concernent que les comptes de bilan.

Les comptes de charges et produits sont exclus.

Les comptes copropriétaires conservent le lien avec chaque copropriétaire.

Une opération unique A_NOUVEAU est créée.

---

# 12. Import historique

Les imports sont réalisés exercice par exercice.

Ordre recommandé :

1. création de l'exercice
2. budgets
3. appels de fonds
4. factures fournisseurs
5. règlements fournisseurs
6. paiements copropriétaires
7. contrôles
8. clôture

Un seul exercice est actif pendant la reprise.

---

# 13. Principes de développement

Toute règle métier doit être implémentée dans un service.

Les contrôleurs ne contiennent aucune logique comptable.

Les entités représentent uniquement les données.

Les écritures sont toujours générées automatiquement.

Toute évolution fonctionnelle doit mettre à jour ce document.