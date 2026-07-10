<?php

namespace App\Twig;

use App\Service\ContexteExerciceService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ContexteExerciceService $contexteExercice
    ) {}

    public function getGlobals(): array
    {
        return [
            'contexte' => $this->contexteExercice,
        ];
    }
}
