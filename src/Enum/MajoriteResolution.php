<?php

namespace App\Enum;

enum MajoriteResolution: string
{
    case ARTICLE_24 = 'article_24';
    case ARTICLE_25 = 'article_25';

    public function label(): string
    {
        return match ($this) {
            self::ARTICLE_24 => 'Article 24',
            self::ARTICLE_25 => 'Article 25',
        };
    }
}
