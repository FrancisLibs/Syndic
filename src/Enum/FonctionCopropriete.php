<?php

namespace App\Enum;

enum FonctionCopropriete: string
{
    case SYNDIC = 'syndic';

    case PRESIDENT_CONSEIL_SYNDICAL =
    'president_conseil_syndical';

    case MEMBRE_CONSEIL_SYNDICAL =
    'membre_conseil_syndical';

    public function label(): string
    {
        return match ($this) {
            self::SYNDIC =>
            'Syndic',

            self::PRESIDENT_CONSEIL_SYNDICAL =>
            'Président du conseil syndical',

            self::MEMBRE_CONSEIL_SYNDICAL =>
            'Membre du conseil syndical',
        };
    }
}
