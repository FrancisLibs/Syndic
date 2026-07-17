# Clôture d'exercice

Version : 1.0

---

# 1. Objectif

La clôture marque la fin d'un exercice comptable.

Elle permet :

- de contrôler la cohérence de l'exercice ;
- de calculer les régularisations ;
- de solder les comptes de gestion ;
- de préparer l'exercice suivant.

La clôture est entièrement guidée par un assistant.

---

# 2. Principe

La clôture ne consiste pas en une seule opération.

Il s'agit d'une succession de traitements dépendant les uns des autres.

```

```

Contrôles préalables
│
▼
Régularisations
│
▼
Clôture des comptes
│
▼
Résultat
│
▼
A nouveaux
│
▼
Création du nouvel exercice
│
▼
Bascule de l'exercice actif

```

---

# 3. Contrôles préalables

Aucune clôture ne peut être réalisée tant que les contrôles suivants ne sont pas validés.

## Exercice actif

L'exercice à clôturer doit être l'exercice actif.

---

## Date de fin

La date de clôture ne peut être antérieure à la date de fin de l'exercice.

Cette règle évite la clôture prématurée d'un exercice en cours.

---

## Budget

Le budget doit être verrouillé.

Cela garantit que les appels de fonds sont définitifs.

---

## Opérations comptables

Toutes les opérations doivent être équilibrées.

Aucune écriture ne peut rester déséquilibrée.

---

## Répartitions

Toutes les charges doivent avoir été réparties.

Les contrôles de validation doivent être satisfaisants.

---

## Module Eau

Si des factures d'eau existent :

- les relevés doivent être saisis ;
- les répartitions d'eau doivent être générées.

---

# 4. Régularisations

Les régularisations constituent la première étape de la clôture.

Pour chaque copropriétaire, le logiciel calcule :

```

```
Charges réelles

-

Appels de fonds

=

Régularisation
```

Une opération comptable est générée pour chaque régularisation non nulle.

---

# 5. Clôture des comptes de gestion

Une fois les régularisations terminées :

- les comptes de charges (classe 6) sont soldés ;
- les comptes de produits (classe 7) sont soldés.

Le résultat est transféré sur le compte :

489000

Résultat de l'exercice.

---

# 6. Le compte 489000

Dans ComptaSyndic V4, le compte 489000 joue un rôle particulier.

Il constitue un compte de transit.

Les comptes de gestion y sont soldés.

Les régularisations répartissent ensuite intégralement le résultat entre les copropriétaires.

À l'issue du traitement :

- les comptes 6 et 7 sont soldés ;
- les copropriétaires supportent le résultat réel ;
- le compte 489000 revient normalement à zéro.

Ce comportement est volontaire.

---

# 7. Génération des à nouveaux

Une fois la clôture terminée :

les comptes de bilan sont repris dans le nouvel exercice.

Les comptes de gestion ne sont jamais reportés.

Les écritures d'ouverture sont générées automatiquement.

---

# 8. Création du nouvel exercice

Le logiciel crée automatiquement :

- le nouvel exercice ;
- les écritures d'ouverture.

Le nouvel exercice devient immédiatement l'exercice actif.

L'exercice clôturé passe automatiquement au statut :

Clôturé.

---

# 9. Contrôles

L'assistant de clôture permet de suivre l'avancement du processus.

Chaque étape est validée individuellement.

Une anomalie interrompt immédiatement le traitement.

Le syndic dispose ainsi d'une vision complète de l'état de l'exercice avant la clôture définitive.

---

# 10. Retour arrière

Une clôture est considérée comme définitive.

Les modifications sur un exercice clôturé ne sont plus autorisées.

Toute correction doit être réalisée avant le lancement de la clôture.

---

# 11. Philosophie

La clôture n'est pas un simple traitement comptable.

Elle constitue la validation finale de l'ensemble des opérations réalisées pendant l'exercice.

Son objectif est de garantir :

- une comptabilité équilibrée ;
- une répartition correcte des charges ;
- un nouvel exercice prêt à être utilisé.

La clôture marque ainsi la transition entre deux exercices tout en assurant la continuité comptable de la copropriété.
