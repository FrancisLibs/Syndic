<?php

namespace App\Enum;

enum OperationType: string
{
    case CHARGE = 'charge';
    case PAIEMENT = 'paiement';
    case APPEL_FONDS = 'appel_fonds';
    case REGULARISATION = 'regularisation';
    case PAIEMENT_FOURNISSEUR = 'paiement_fournisseur';
    case A_NOUVEAU = 'a_nouveau';
    case CLOTURE = 'cloture';
}
