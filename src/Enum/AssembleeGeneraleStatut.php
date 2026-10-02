<?php

namespace App\Enum;

enum AssembleeGeneraleStatut: string
{
    case PREPARATION = 'preparation';
    case EN_COURS = 'en_cours';
    case CLOTUREE = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::PREPARATION => 'En préparation',
            self::EN_COURS => 'En cours',
            self::CLOTUREE => 'Clôturée',
        };
    }
}
