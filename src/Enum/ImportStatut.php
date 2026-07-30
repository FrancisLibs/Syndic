<?php

namespace App\Enum;

enum ImportStatut: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case TRAITEMENT = 'TRAITEMENT';
    case TRAITEE = 'TRAITEE';
    case ERREUR = 'ERREUR';
}
