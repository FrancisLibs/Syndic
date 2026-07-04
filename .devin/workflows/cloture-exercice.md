---
description: How to close an accounting exercise (clôture d'exercice)
---

# Cloture d'Exercice Workflow

This workflow describes the complete process to close an accounting exercise in the comptaSyndic application.

## Prerequisites

- The exercise must be active (not already closed)
- All budgets for the exercise must be locked
- All operations must be balanced
- The exercise must have a valid date range

## Steps

### 1. Verify Exercise Pre-conditions
Run the validation checks to ensure the exercise can be closed:
- Check exercise is not already closed
- Check exercise is active
- Verify all budgets are locked
- Verify all operations are balanced

This is handled by `ClotureExerciceService::verifierExercice()`

### 2. Calculate Regularizations
Calculate the difference between actual charges and calls for funds (appels de fonds) for each copropriétaire:
- Get all copropriétaires from the copropriété
- For each copropriétaire, calculate total charges from répartitions
- For each copropriétaire, calculate total appels de fonds
- Compute the regularization amount (charges - appels)

This is handled by `ClotureExerciceService::calculerRegularisations()`

### 3. Create New Exercise
Create the next year's exercise:
- Generate name (e.g., "Exercice 2027")
- Set start date (day after current exercise end date)
- Set end date (one year after start date, minus one day)
- Set as active and not closed
- Link to the same copropriété

This is handled by `ClotureExerciceService::creerNouvelExercice()`

### 4. Switch Exercises (Bascule)
Deactivate the current exercise and activate the new one:
- Set current exercise: actif=false, cloture=true
- Set new exercise: actif=true
- Find the next exercise in sequence

This is handled by `ClotureExerciceService::basculer()`

### 5. Regularize Charges (Optional - currently commented out)
Generate accounting entries for charge regularizations:
- Calculate total actual expenses (FactureFournisseur)
- Calculate total calls for funds per copropriétaire
- Create regularization operation
- Generate adjustment entries for each copropriétaire account
- Handle counterparty accounting entry (to maintain double-entry balance)

This is handled by `ClotureExerciceService::regulariserCharges()`

### 6. Generate New Entries (À-nouveaux) (Optional - not implemented)
Carry forward balances from classes 1-5 to the new exercise:
- Identify accounts with carry-forward balances
- Generate opening entries in the new exercise
- Maintain accounting continuity

This is handled by `ClotureExerciceService::genererAnouveau()`

### 7. Lock Exercise (Optional - currently commented out)
Finalize the closure by locking the exercise:
- Prevent further modifications
- Mark as officially closed

This is handled by `ClotureExerciceService::cloturerExercice()`

## Execution

The main entry point is `ClotureController::cloturer()` which calls:
```php
$clotureService->executerCloture($exercice);
```

## Current Status

- Steps 1-4 are implemented
- Step 5 is implemented but commented out
- Step 6 is not implemented (empty method)
- Step 7 is commented out in the main method

## Notes

- The service uses Doctrine EntityManager for database operations
- All changes are flushed at the end of `executerCloture()`
- The method returns the newly created exercise
- Error handling uses try-catch in the controller with flash messages
