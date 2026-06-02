<?php

namespace App\Enum;

enum ModeRepartition: string
{
    case TANTIEMES = 'tantiemes';
    case EGALITAIRE = 'egalitaire';

    public function label(): string
    {
        return match ($this) {
            self::TANTIEMES => 'Tantièmes',
            self::EGALITAIRE => 'Égalitaire',
        };
    }
}