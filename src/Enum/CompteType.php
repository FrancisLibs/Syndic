<?php

namespace App\Enum;

enum CompteType: string
{
    case ACTIF = 'actif';

    case PASSIF = 'passif';

    case CHARGE = 'charge';

    case PRODUIT = 'produit';

    case TIERS = 'tiers';

    case BANQUE = 'banque';
}
