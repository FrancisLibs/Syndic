<?php

namespace App\Enum;

enum ExerciceStatut: string
{
    case OUVERT = 'ouvert';

    case REGULARISATIONS_CALCULEES = 'regularisations_calculees';

    case REGULARISATIONS_VALIDEES = 'regularisations_validees';

    case ANOUVEAUX_GENERES = 'anouveaux_generes';

    case CLOTURE = 'cloture';
}