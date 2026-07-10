<?php

namespace App\Twig;

use App\Service\ContexteExerciceService;

class AppTwigGlobals
{
    public function __construct(
        private ContexteExerciceService $contexteExercice
    ) {}

    public function getExercice(): mixed
    {
        return $this->contexteExercice->getExercice();
    }

    public function getExercices(): array
    {
        return $this->contexteExercice->getExercices();
    }
}
