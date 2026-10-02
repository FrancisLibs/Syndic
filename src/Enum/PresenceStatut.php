<?php

namespace App\Enum;

enum PresenceStatut: string
{
    case PRESENT = 'present';
    case REPRESENTE = 'represente';
    case VOTE_CORRESPONDANCE = 'vote_correspondance';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Présent',
            self::REPRESENTE => 'Représenté',
            self::VOTE_CORRESPONDANCE => 'Vote par correspondance',
            self::ABSENT => 'Absent',
        };
    }
}
