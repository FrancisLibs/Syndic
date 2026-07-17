# Module Eau

Version : 1.0

---

# 1. Objectif

Le module Eau permet de répartir les dépenses d'eau entre les copropriétaires à partir des consommations réellement constatées.

Contrairement aux autres charges, les factures d'eau ne peuvent pas être réparties immédiatement.

La répartition est différée jusqu'à la saisie des relevés des compteurs.

---

# 2. Principe général

Le traitement de l'eau repose sur quatre étapes :

```
Factures d'eau
        │
        ▼
Comptabilisation

        │
        ▼
Relevés des compteurs

        │
        ▼
Calcul des consommations

        │
        ▼
Répartition des charges d'eau
```

Chaque étape dépend de la précédente.

---

# 3. Les compteurs

Le logiciel distingue deux types de compteurs :

## Compteur général

Le compteur général mesure la consommation totale de la copropriété.

Il n'est associé à aucun lot.

Une seule valeur est attendue par exercice.

---

## Compteurs individuels

Chaque lot possède un compteur individuel.

Ils servent à calculer la consommation réelle de chaque copropriétaire.

---

# 4. Les relevés

Pour chaque exercice, un relevé est enregistré pour chaque compteur.

Le logiciel utilise :

- le dernier relevé de l'exercice précédent ;
- le relevé de l'exercice courant.

La différence constitue la consommation.

```
Consommation

=

Nouvel index

-

Ancien index
```

---

# 5. Contrôles

Avant tout calcul, plusieurs contrôles sont effectués.

## Relevés complets

Tous les compteurs doivent posséder un relevé.

---

## Index cohérents

Le nouvel index doit être supérieur ou égal au précédent.

---

## Consommation générale

La consommation du compteur général doit être positive.

---

## Contrôle de cohérence

La somme des consommations individuelles ne peut jamais dépasser la consommation générale.

Dans le cas contraire, le traitement est interrompu.

---

# 6. Calcul du prix du m³

Le prix du mètre cube est calculé automatiquement.

```
Prix du m³

=

Total des factures d'eau

/

Consommation générale
```

Le volume indiqué sur les factures n'est volontairement pas utilisé.

Le logiciel considère que seul le coût réel payé est pertinent.

---

# 7. Répartition

La répartition comporte deux parties.

## Part individuelle

Chaque copropriétaire paie sa consommation réelle.

```
Consommation

×

Prix du m³
```

---

## Part commune

La différence entre :

- le compteur général ;
- la somme des compteurs individuels ;

correspond à la consommation des parties communes.

Cette consommation est répartie selon les tantièmes généraux.

Cette méthode a été retenue pour cette copropriété.

---

# 8. Arrondis

Chaque montant est arrondi à deux décimales.

Après calcul :

```
Somme des répartitions

=

Total des factures
```

Si un écart apparaît, il est affecté à la part commune du lot présentant le montant total le plus élevé.

Cette méthode garantit une égalité parfaite.

---

# 9. Les répartitions Eau

Les répartitions d'eau sont enregistrées dans la table :

Repartition

Particularité :

Le champ

ecriture

est volontairement nul.

Cette conception signifie que :

- la charge est déjà comptabilisée ;
- seule sa ventilation est calculée.

Il n'existe donc aucune double comptabilisation.

---

# 10. Validation

Après génération, plusieurs contrôles sont disponibles.

Le logiciel vérifie notamment :

- le montant total des factures ;
- le montant total réparti ;
- les écarts éventuels.

L'objectif est de permettre au syndic de contrôler les calculs avant la clôture.

---

# 11. Modification des relevés

Une fois les répartitions générées, les relevés sont considérés comme validés.

Une évolution prévue consiste à permettre :

- la suppression automatique des répartitions d'eau ;
- la modification des relevés ;
- une nouvelle génération.

Cette fonctionnalité figure dans le backlog du projet.

---

# 12. Intégration avec la clôture

Le traitement de l'eau doit être terminé avant la clôture de l'exercice.

L'assistant de clôture vérifie que :

- les factures d'eau sont réparties ;
- les contrôles sont satisfaisants.

Dans le cas contraire, la clôture est refusée.

---

# 13. Résumé

Le module Eau constitue une exception dans le modèle comptable de ComptaSyndic V4.

Les factures sont comptabilisées immédiatement.

La répartition est volontairement différée jusqu'à la connaissance des consommations réelles.

Cette approche garantit une répartition fidèle des dépenses d'eau tout en conservant une comptabilité cohérente et entièrement traçable.