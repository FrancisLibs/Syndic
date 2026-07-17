# Appels de fonds

## Principe

Les appels de fonds constituent la principale source de financement de la copropriété.

Ils sont générés à partir d'un budget préalablement validé.

Un budget ne peut produire qu'un seul appel de fonds.

Une fois l'appel généré, le budget est automatiquement verrouillé afin de garantir la cohérence des calculs.

---

## Répartition

Les appels sont répartis entre les lots selon les tantièmes généraux.

Si un lot possède plusieurs copropriétaires, le montant du lot est ensuite réparti selon le pourcentage de détention de chacun.

L'ensemble des calculs est réalisé automatiquement.

Les écarts d'arrondis sont corrigés sur la dernière ligne afin que :

Somme des lignes = montant total de l'appel.

---

## Comptabilisation

La génération d'un appel crée automatiquement une opération comptable.

Débit

Comptes des copropriétaires

Crédit

701000 - Appels de fonds

Une écriture est créée pour chaque copropriétaire.

L'opération est toujours équilibrée.

---

# Paiements copropriétaires

## Principe

Les paiements sont enregistrés indépendamment des appels de fonds.

Chaque paiement est ensuite affecté automatiquement aux appels restant dus.

Le syndic n'a aucune affectation manuelle à réaliser.

---

## Ordre d'affectation

Les paiements sont affectés :

- du plus ancien appel au plus récent ;
- uniquement sur les lignes non soldées.

Cette méthode garantit une situation comptable cohérente.

---

## Paiement partiel

Un paiement peut solder totalement ou partiellement un appel.

Les montants réglés sont mis à jour automatiquement.

Lorsqu'une ligne atteint son montant total :

Soldée = Oui.

---

## Comptabilisation

Chaque paiement produit automatiquement une opération comptable.

Débit

512000 Banque

Crédit

Compte copropriétaire

Aucune écriture supplémentaire n'est nécessaire.

---

# Factures fournisseurs

## Principe

Les factures fournisseurs représentent les charges réelles de la copropriété.

Chaque facture est rattachée :

- à un exercice ;
- à un fournisseur ;
- à un type de charge.

Le type de charge détermine :

- le compte comptable utilisé ;
- le mode de répartition ;
- si la charge correspond ou non à une consommation d'eau.

---

## Comptabilisation

Débit

Compte de charge

Crédit

Compte fournisseur

Une seule opération est créée par facture.

---

## Cas particulier des factures d'eau

Les factures d'eau sont immédiatement comptabilisées.

En revanche, elles ne sont pas réparties.

La raison est simple :

Au moment de la facture, les consommations individuelles sont inconnues.

La répartition sera réalisée après la saisie des relevés.
