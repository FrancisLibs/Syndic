# ComptaSyndic V4 - Architecture Diagram

## Overview
ComptaSyndic V4 is a Symfony 6.4 application for condominium/co-op property management and accounting (French: "comptabilité syndic"). It manages properties, owners, units, budgets, fund calls, supplier invoices, and accounting operations.

## Technology Stack
- **Framework**: Symfony 6.4
- **Database**: MySQL/PostgreSQL via Doctrine ORM
- **Admin Interface**: EasyAdmin Bundle
- **Templating**: Twig
- **Frontend**: Symfony UX Turbo + Stimulus
- **Deployment**: Docker Compose
- **PHP**: 8.1+

## Architecture Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                         PRESENTATION LAYER                      │
├─────────────────────────────────────────────────────────────────┤
│  Controllers (19)              Templates (80)                   │
│  - HomeController                - base.html.twig              │
│  - CoproprieteController         - copropriete/                │
│  - CoproprietaireController      - coproprietaire/             │
│  - LotController                 - lot/                        │
│  - ExerciceController            - exercice/                   │
│  - BudgetController              - budget/                     │
│  - AppelFondController           - appel_fond/                 │
│  - OperationController           - operation/                  │
│  - ComptabiliteController        - comptabilite/               │
│  - PaiementController            - paiement/                   │
│  - FournisseurController         - fournisseur/                │
│  - FactureFournisseurController  - facture_fournisseur/       │
│  - ClotureController             - cloture/                    │
│  - TypeChargeController          - type_charge/                │
│  - CompteController              - compte/                     │
│  - ChargeController              - charge/                     │
│  - PaiementFournisseurController - paiement_fournisseur/       │
│  - FactureController             - facture/                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                          BUSINESS LAYER                          │
├─────────────────────────────────────────────────────────────────┤
│  Services (15)                                                   │
│  - ClotureExerciceService        (Year-end closing)             │
│  - BudgetGeneratorService        (Budget generation)            │
│  - GenerateurAppelFondService    (Fund call generation)         │
│  - GenerateurEcritureAppelFondService                          │
│  - GenerationAppelFondService                                  │
│  - GenerationFactureFournisseurService                         │
│  - GenerationPaiementService                                   │
│  - GenerationRepartitionService                                 │
│  - RepartitionService            (Cost distribution)            │
│  - RegularisationService         (Adjustments)                  │
│  - AffectationPaiementService    (Payment allocation)           │
│  - CalculChargesReellesService   (Real charges calculation)     │
│  - CompteCoproprietaireService                                  │
│  - LotOwnershipManagerService                                   │
│  - ReglementFactureFournisseurService                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                         DATA ACCESS LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  Repositories (19)              Doctrine ORM                     │
│  - CoproprieteRepository        - Entity Management            │
│  - CoproprietaireRepository     - Query Builder                │
│  - LotRepository                - Migrations                    │
│  - ExerciceRepository           - Relationships                 │
│  - BudgetRepository                                             │
│  - AppelFondRepository                                          │
│  - OperationRepository                                           │
│  - EcritureRepository                                            │
│  - CompteRepository                                             │
│  - TypeChargeRepository                                          │
│  - FournisseurRepository                                        │
│  - FactureFournisseurRepository                                 │
│  - PaiementRepository                                            │
│  - RepartitionRepository                                        │
│  - LotCoproprietaireRepository                                  │
│  - LigneBudgetRepository                                         │
│  - LigneAppelFondRepository                                     │
│  - AffectationPaiementRepository                                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                          DATABASE LAYER                           │
├─────────────────────────────────────────────────────────────────┤
│  MySQL/PostgreSQL                                                │
│  - Tables for each entity                                        │
│  - Foreign key relationships                                    │
│  - Indexes for performance                                       │
└─────────────────────────────────────────────────────────────────┘
```

## Domain Model (Entity Relationships)

```
┌──────────────────┐
│   Copropriete    │ (Property/Building)
│  - nom           │
│  - adresse       │
│  - tantiemesBase │
└────────┬─────────┘
         │ 1
         │ has many
         │ *
    ┌────┴────┐
    │         │
    ↓         ↓
┌────────┐ ┌──────────┐
│  Lot   │ │Exercice  │ (Fiscal Year)
│ - ref  │ │ - nom    │
│ - tanti│ │ - dates  │
└───┬────┘ │ - statut │
    │      └────┬─────┘
    │ *           │ 1
    │ owned by    │ has many
    │             │ *
    │      ┌──────┴──────┐
    │      │             │
    │      ↓             ↓
    │ ┌─────────┐  ┌──────────┐
    │ │Budget   │  │Operation │
    │ │ - lignes│  │ - ecritures│
    │ └────┬────┘  └────┬─────┘
    │      │            │
    │      │ 1          │ 1
    │      │ has        │ has
    │      │ many       │ many
    │      │            │
    │      ↓            ↓
    │ ┌─────────┐  ┌──────────┐
    │ │AppelFond│  │Ecriture  │
    │ │ - lignes│  │ - debit  │
    │ └────┬────┘  │ - credit │
    │      │       └────┬─────┘
    │      │            │
    │      │            │ *
    │      │            │ distributed to
    │      │            │
    │      │      ┌─────┴─────┐
    │      │      │           │
    │      │      ↓           ↓
    │      │ ┌─────────┐ ┌──────────┐
    │      │ │Repartition│ │Compte   │
    │      │ │ - montant│ │ - numero │
    │      │ └────┬────┘ └────┬─────┘
    │      │      │          │
    │      │      │          │ *
    │      │      │          │ used by
    │      │      │          │
    │      │      │    ┌─────┴─────┐
    │      │      │    │           │
    │      │      ↓    ↓           ↓
    │      │ ┌──────────┐ ┌──────────────┐
    │      │ │Coproprietaire│ │TypeCharge  │
    │      │ │ - nom    │ │ - compte    │
    │      │ │ - compte │ └─────────────┘
    │      │ └────┬─────┘
    │      │      │
    │      │      │ *
    │      │      │ linked via
    │      │      │
    │      │      ↓
    │      │ ┌──────────────────┐
    │      │ │LotCoproprietaire│
    │      │ │ - dateDebut     │
    │      │ │ - dateFin       │
    │      │ └─────────────────┘
    │      │
    │      └──────────────────────────┐
    │                                 │
    └─────────────────────────────────┘
```

## Core Business Workflows

### 1. Property Setup Workflow
```
Create Copropriete (Building)
    ↓
Create Lots (Units) with tantiemes (ownership shares)
    ↓
Create Comptes (Account numbers)
    ↓
Create Coproprietaires (Owners)
    ↓
Link Owners to Lots via LotCoproprietaire (with date ranges)
```

### 2. Annual Budget Workflow
```
Create Exercice (Fiscal Year)
    ↓
Create Budget for Exercice
    ↓
Add LigneBudget items (expense categories)
    ↓
Lock Budget (verrouille = true)
```

### 3. Fund Call Workflow (Appel de Fonds)
```
Create AppelFond from Budget
    ↓
Generate LigneAppelFond for each Coproprietaire
    ↓
Calculate amounts based on tantiemes
    ↓
Generate Operation (accounting entry)
    ↓
Generate Ecriture lines (debit/credit)
```

### 4. Payment Workflow
```
Receive Paiement from Coproprietaire
    ↓
AffectationPaiementService allocates to outstanding calls
    ↓
Update LigneAppelFond status
    ↓
Generate accounting Ecriture
```

### 5. Supplier Invoice Workflow
```
Create Fournisseur (Supplier)
    ↓
Create TypeCharge linked to Compte
    ↓
Create FactureFournisseur
    ↓
Generate Operation for the invoice
    ↓
Generate Ecriture lines (debit expense, credit supplier)
    ↓
PaiementFournisseur to pay the invoice
```

### 6. Year-End Closing Workflow (Cloture)
```
ClotureExerciceService.executerCloture()
    ↓
Verify all budgets are locked
    ↓
Verify all operations are balanced
    ↓
Calculate regularisations (adjustments)
    ↓
Create new Exercice for next year
    ↓
Switch active status (old: cloture=true, new: actif=true)
    ↓
Generate à-nouveau entries (carry forward balances)
```

## Key Entity Relationships

### Copropriete (Building)
- Has many Lots (units)
- Has many Exercices (fiscal years)
- Has many Budgets

### Lot (Unit)
- Belongs to Copropriete
- Has many LotCoproprietaire relationships (ownership history)
- Has tantiemes (ownership share percentage)
- Linked to Operations and Ecritures

### Coproprietaire (Owner)
- Has Compte (account number)
- Has many LotCoproprietaire relationships
- Receives LigneAppelFond (fund calls)
- Makes Paiements
- Has Repartition (cost distributions)

### Exercice (Fiscal Year)
- Belongs to Copropriete
- Has many Budgets
- Has many Ecritures
- Has many FactureFournisseur
- Has many Paiements
- Has statut (OUVERT/CLOTURE)
- Has actif flag

### Budget
- Belongs to Exercice and Copropriete
- Has many LigneBudget
- Has many AppelFond
- Can be verrouille (locked)

### AppelFond (Fund Call)
- Belongs to Budget
- Has many LigneAppelFond (per owner)
- Linked to Operation
- Has dateAppel and dateReglement

### Operation (Accounting Operation)
- Has many Ecriture (double-entry lines)
- Has type (DEPENSE, RECETTE, REGULARISATION, APPEL_FOND)
- Has statut (BROUILLON, VALIDE)
- Must be balanced (debit = credit)

### Ecriture (Accounting Entry)
- Belongs to Compte
- Belongs to Operation
- Belongs to Exercice
- Has debit or credit (never both)
- Can have Repartition (cost distribution)

## Configuration Structure

```
config/
├── bundles.php           # Bundle configuration
├── services.yaml          # Service configuration
├── routes.yaml           # Routing (attribute-based)
└── packages/
    ├── doctrine.yaml     # Database configuration
    ├── security.yaml     # Security configuration
    ├── twig.yaml         # Twig configuration
    └── ...
```

## Key Features

1. **Multi-Property Management**: Handle multiple condominiums
2. **Owner Tracking**: Track ownership changes over time with date ranges
3. **Budget Management**: Annual budgets with line items
4. **Fund Calls**: Generate payment requests based on ownership shares (tantiemes)
5. **Double-Entry Accounting**: Full accounting system with debit/credit
6. **Supplier Management**: Track suppliers and invoices
7. **Payment Tracking**: Track owner payments and allocations
8. **Year-End Closing**: Automated fiscal year closing with adjustments
9. **Cost Distribution**: Automatic cost distribution among owners
10. **Account Validation**: Built-in validation for balanced operations

## Database Schema Highlights

- **Unique Constraints**: Budget per exercice per copropriete
- **Cascade Operations**: Persist/remove cascades for child entities
- **Orphan Removal**: Automatic cleanup of orphaned records
- **Decimal Precision**: Financial amounts use DECIMAL(10,2)
- **Date Immutable**: Dates use DateTimeImmutable for safety
- **Enum Types**: Status fields use PHP enums

## Security & Validation

- Form validation via Symfony Form component
- Entity validation via Symfony Validator
- Business logic validation in Services
- Double-entry accounting validation (balanced operations)
- Date range validation for ownership periods
- Budget locking to prevent modifications after fund calls
