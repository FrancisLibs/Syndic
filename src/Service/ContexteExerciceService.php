<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Repository\ExerciceRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class ContexteExerciceService
{
    private const SESSION_KEY = 'exercice_courant_id';

    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private RequestStack $requestStack,
    ) {}

    public function getExercice(): ?Exercice
    {
        $session = $this->requestStack->getSession();

        $exerciceId = $session->get(self::SESSION_KEY);

        if ($exerciceId) {
            $exercice = $this->exerciceRepository->find($exerciceId);

            if ($exercice) {
                return $exercice;
            }
        }

        $exercice = $this->exerciceRepository->findActif();

        if ($exercice) {
            $this->setExercice($exercice);
        }

        return $exercice;
    }

    public function setExercice(Exercice $exercice): void
    {
        $this->requestStack
            ->getSession()
            ->set(self::SESSION_KEY, $exercice->getId());
    }

    public function getExercices(): array
    {
        return $this->exerciceRepository->findBy(
            [],
            ['dateDebut' => 'DESC']
        );
    }
}
