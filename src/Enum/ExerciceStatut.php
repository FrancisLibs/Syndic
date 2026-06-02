<?php

namespace App\Enum;

enum ExerciceStatut: string
{
    case OUVERT = 'ouvert';
    case PROVISOIRE = 'provisoire';
    case CLOTURE = 'cloture';
}