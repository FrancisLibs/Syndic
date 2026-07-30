<?php

namespace App\Enum;

enum ImportEtat: string
{
    case A_IMPORTER = 'a_importer';
    case IMPORTE = 'importe';
    case ERREUR = 'erreur';

    public function getLabel(): string
    {
        return match ($this) {
            self::A_IMPORTER => 'À importer',
            self::IMPORTE => 'Importé',
            self::ERREUR => 'Erreur',
        };
    }
}
