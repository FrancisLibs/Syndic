<?php

namespace App\Service\Initialisation;

use App\Dto\Initialisation\InitialisationComptable;
use App\Entity\Exercice;

final class InitialisationComptableService
{
    public function __construct(
        private readonly VerificationInitialisationService $verificationService,
    ) {}

    public function preparer(
        Exercice $exercice,
    ): InitialisationComptable {
        $initialisation = new InitialisationComptable(
            $exercice,
            $exercice->getDateDebut(),
            sprintf(
                'À-nouveaux %s',
                $exercice->getNom()
            )
        );

        $this->verificationService->verifier(
            $initialisation
        );

        return $initialisation;
    }
}
