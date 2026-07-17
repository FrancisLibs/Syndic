# Historique des décisions d'architecture

Version : 1.0

---

# Objectif

Ce document recense les principales décisions prises lors de la conception de ComptaSyndic V4.

Il ne décrit pas le fonctionnement du logiciel.

Il explique pourquoi certaines solutions ont été retenues.

Son objectif est de conserver la mémoire du projet.

---

# 1. Toute la comptabilité est générée automatiquement

Décision :

L'utilisateur ne saisit jamais d'écriture comptable.

Justification :

Le syndic bénévole travaille avec des opérations de gestion.

La comptabilité doit être une conséquence de ces opérations et non une activité distincte.

Conséquences :

- moins d'erreurs de saisie ;
- comptabilité homogène ;
- écritures toujours traçables.

---

# 2. Les services contiennent toute la logique métier

Décision :

Les contrôleurs restent volontairement très légers.

Justification :

Les traitements doivent être réutilisables, testables et indépendants de l'interface utilisateur.

Conséquences :

- code plus lisible ;
- maintenance simplifiée ;
- architecture évolutive.

---

# 3. Les répartitions sont le cœur du logiciel

Décision :

Toute charge est répartie avant toute régularisation.

Justification :

Le véritable objectif d'une comptabilité de copropriété est de déterminer qui supporte chaque dépense.

Les répartitions sont donc la base :

- des contrôles ;
- des régularisations ;
- des états financiers.

---

# 4. Les arrondis sont corrigés sur une seule ligne

Décision :

Les écarts d'arrondis sont toujours affectés à une seule ligne.

Justification :

Garantir :

Somme des lignes = montant d'origine.

Cette règle est utilisée pour :

- appels de fonds ;
- répartitions ;
- eau.

---

# 5. Les factures d'eau ne sont jamais réparties immédiatement

Décision :

La comptabilisation et la répartition sont deux traitements distincts.

Justification :

La consommation réelle est inconnue lors de la saisie de la facture.

La répartition est donc différée.

---

# 6. Les répartitions d'eau ne possèdent pas d'écriture

Décision :

Le champ ecriture est volontairement nul.

Justification :

La charge existe déjà comptablement.

La répartition représente uniquement sa ventilation.

Cela évite toute double comptabilisation.

---

# 7. Les consommations communes sont réparties aux tantièmes

Décision :

Les consommations des parties communes ne sont pas réparties au prorata des consommations individuelles.

Justification :

Ce choix correspond au règlement de copropriété retenu pour le projet.

Il garantit une répartition stable et compréhensible.

---

# 8. Le compte 489000 est un compte de transit

Décision :

Le résultat transite par le compte 489000.

Justification :

Le résultat est ensuite entièrement réparti entre les copropriétaires.

Le compte revient donc normalement à zéro après les régularisations.

Ce comportement est volontaire.

---

# 9. Les contrôles sont visibles

Décision :

Les traitements importants disposent d'un écran de validation.

Justification :

Le logiciel ne doit jamais fonctionner comme une "boîte noire".

Le syndic doit pouvoir vérifier :

- les répartitions ;
- les écarts ;
- les consommations ;
- les régularisations.

---

# 10. Les erreurs doivent être détectées le plus tôt possible

Décision :

Les traitements effectuent leurs contrôles avant toute génération.

Justification :

Il est préférable d'interrompre un traitement que de corriger une comptabilité devenue incohérente.

---

# 11. Une clôture est définitive

Décision :

Un exercice clôturé n'est plus modifiable.

Justification :

La clôture constitue la validation comptable de l'exercice.

Toute correction doit intervenir avant son lancement.

---

# 12. Le logiciel est conçu autour des traitements métier

Décision :

Les écrans suivent le travail quotidien du syndic.

Justification :

Le logiciel ne doit pas imposer une logique comptable.

Il accompagne les tâches réelles :

- préparer un budget ;
- appeler des fonds ;
- enregistrer une facture ;
- saisir un paiement ;
- relever les compteurs ;
- clôturer un exercice.

La comptabilité est générée automatiquement.

---

# Conclusion

Les décisions décrites dans ce document constituent les principes fondateurs de ComptaSyndic V4.

Toute évolution future du logiciel devrait être évaluée au regard de ces principes afin de préserver la cohérence de l'application.
