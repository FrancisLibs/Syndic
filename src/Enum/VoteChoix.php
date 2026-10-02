<?php

namespace App\Enum;

enum VoteChoix: string
{
    case POUR = 'pour';
    case CONTRE = 'contre';
    case ABSTENTION = 'abstention';

    public function label(): string
    {
        return match ($this) {
            self::POUR => 'Pour',
            self::CONTRE => 'Contre',
            self::ABSTENTION => 'Abstention',
        };
    }
}
