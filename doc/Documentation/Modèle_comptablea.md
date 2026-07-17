# Modèle comptable

Version : 1.0

---

# 1. Objet du document

Ce document décrit le modèle comptable utilisé par ComptaSyndic V4.

Il ne s'agit pas d'un manuel de comptabilité générale mais de la description des principes retenus pour la gestion d'une copropriété.

Il explique :

- les traitements réalisés par l'application ;
- les écritures générées automatiquement ;
- les choix d'architecture comptable ;
- les règles métier.

Ce document constitue la référence fonctionnelle du logiciel.

---

# 2. Philosophie

ComptaSyndic V4 repose sur un principe fondamental :

> L'utilisateur ne saisit jamais directement les écritures comptables.

La comptabilité est produite automatiquement à partir des opérations de gestion.

Le syndic travaille uniquement avec des objets métier :

- Budget
- Appel de fonds
- Paiement
- Facture fournisseur
- Relevé de compteur
- Clôture d'exercice

Chaque action entraîne automatiquement la création des écritures comptables nécessaires.

Cette approche présente plusieurs avantages :

- diminution des erreurs de saisie ;
- homogénéité des traitements ;
- traçabilité complète ;
- comptabilité toujours équilibrée.

---

# 3. Cycle comptable

Le cycle complet d'un exercice est le suivant :

```
Création de l'exercice
        │
        ▼
Création du budget
        │
        ▼
Génération des appels de fonds
        │
        ▼
Paiements copropriétaires

──────────────────────────────

Factures fournisseurs
        │
        ▼
Répartition des charges

──────────────────────────────

Factures d'eau
        │
        ▼
Relevés des compteurs
        │
        ▼
Répartition Eau

──────────────────────────────

Régularisations

──────────────────────────────

Clôture

──────────────────────────────

A nouveaux

──────────────────────────────

Nouvel exercice
```

Chaque étape est indépendante mais prépare la suivante.

---

# 4. Principes comptables

Le logiciel applique la comptabilité en partie double.

Chaque opération est équilibrée.

Aucune écriture ne peut être enregistrée si :

- le débit est différent du crédit ;
- un compte est absent ;
- l'exercice est invalide.

Toutes les écritures sont rattachées à :

- une opération ;
- un exercice.

Cette règle garantit une traçabilité complète.

---

# 5. Les opérations

Toutes les écritures sont regroupées dans une opération.

Une opération représente un événement métier unique.

Exemples :

- Appel de fonds
- Paiement copropriétaire
- Facture fournisseur
- Paiement fournisseur
- Régularisation
- Clôture

Une opération peut contenir plusieurs écritures.

---

# 6. Les écritures

Une écriture représente un mouvement sur un compte comptable.

Chaque écriture possède notamment :

- un compte ;
- un débit ou un crédit ;
- une date ;
- un exercice ;
- éventuellement un copropriétaire ;
- éventuellement un lot.

Les écritures sont générées automatiquement.

Aucune écriture n'est créée manuellement.

---

# 7. Les appels de fonds

Les appels de fonds proviennent exclusivement d'un budget.

Le budget ne produit aucune écriture.

La génération d'un appel produit :

- un appel de fonds ;
- les lignes d'appel ;
- les écritures comptables.

Les montants sont répartis selon les tantièmes.

Les arrondis sont corrigés automatiquement afin que la somme des lignes soit exactement égale au montant de l'appel.

Écriture générée :

Débit

Compte copropriétaire

Crédit

701000 - Produits des appels de fonds

---

# 8. Les paiements copropriétaires

Chaque paiement est enregistré indépendamment.

Le logiciel affecte automatiquement le paiement aux appels de fonds restant dus.

Les affectations sont réalisées du plus ancien appel vers le plus récent.

Le paiement peut être :

- total ;
- partiel.

Écriture générée :

Débit

512000 Banque

Crédit

Compte copropriétaire

---

# 9. Les factures fournisseurs

Chaque facture est liée :

- à un fournisseur ;
- à un type de charge ;
- à un exercice.

Le type de charge détermine :

- le compte comptable ;
- le mode de répartition ;
- si la charge concerne l'eau.

Écriture générée :

Débit

Compte de charge

Crédit

Compte fournisseur

---

# 10. Les répartitions

Les répartitions constituent le cœur fonctionnel du logiciel.

Une charge comptable appartient initialement à la copropriété.

La répartition détermine la part supportée par chaque copropriétaire.

Une répartition mémorise :

- le lot ;
- le copropriétaire ;
- le montant ;
- les tantièmes ;
- l'écriture d'origine (sauf eau).

Les répartitions servent de base :

- aux contrôles ;
- aux régularisations ;
- aux états de synthèse.

Sans répartitions, il serait impossible de connaître le coût réel supporté par chaque copropriétaire.

---

# 11. Cas particulier du module Eau

Les factures d'eau sont comptabilisées immédiatement.

En revanche, aucune répartition n'est créée.

La raison est simple :

Au moment de la facture, les consommations individuelles sont inconnues.

La répartition intervient uniquement après :

- la saisie des relevés ;
- le calcul des consommations.

Les répartitions d'eau ne sont volontairement rattachées à aucune écriture.

Elles représentent uniquement une ventilation d'une charge déjà comptabilisée.

Cette architecture simplifie :

- les calculs ;
- les contrôles ;
- les régularisations.

---

# 12. Les régularisations

Les régularisations constituent l'aboutissement de l'exercice.

Le logiciel compare :

- les appels de fonds ;
- les charges réellement réparties.

Pour chaque copropriétaire :

Régularisation = Charges réelles − Appels de fonds

Le résultat peut être :

- positif ;
- négatif ;
- nul.

Les écritures de régularisation sont générées automatiquement.

---

# 13. Clôture de l'exercice

La clôture est guidée par un assistant.

Avant toute clôture, plusieurs contrôles sont effectués :

- exercice actif ;
- date de fin atteinte ;
- budget verrouillé ;
- opérations équilibrées ;
- répartitions validées ;
- traitement de l'eau terminé.

La clôture génère ensuite :

- les régularisations ;
- la clôture des comptes de gestion ;
- les à nouveaux ;
- le nouvel exercice.

---

# 14. Le compte 489000

Le compte 489000 est utilisé comme compte de transit.

Les comptes de charges et de produits sont soldés sur ce compte.

Les régularisations répartissent ensuite entièrement le résultat entre les copropriétaires.

À la fin du traitement :

- les comptes de gestion sont soldés ;
- le résultat est réparti ;
- le compte 489000 revient à zéro.

Ce comportement est normal.

---

# 15. Contrôles

ComptaSyndic V4 réalise de nombreux contrôles automatiques.

Parmi eux :

- équilibre des opérations ;
- équilibre des répartitions ;
- contrôle des consommations d'eau ;
- contrôle des régularisations ;
- contrôle de la clôture.

Ces contrôles permettent de détecter les anomalies avant qu'elles n'affectent la comptabilité.

---

# 16. Conclusion

Le modèle comptable de ComptaSyndic V4 repose sur une idée simple :

Le syndic réalise des opérations de gestion.

Le logiciel produit automatiquement une comptabilité conforme, équilibrée et entièrement traçable.

La notion de répartition constitue le cœur fonctionnel du système et relie la comptabilité générale à la situation individuelle de chaque copropriétaire.

L'objectif n'est pas uniquement de tenir une comptabilité, mais de fournir au syndic un outil fiable permettant de connaître à tout moment la situation financière de la copropriété et de chacun de ses copropriétaires.
