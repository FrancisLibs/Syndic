<?php

namespace App\Enum;

enum ReglementFournisseurType: string
{
    case PAIEMENT = 'paiement';
    case REMBOURSEMENT = 'remboursement';
}
