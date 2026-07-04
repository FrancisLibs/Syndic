<?php

namespace App\Dto\Cloture;

use App\Entity\Exercice;

final class EtatCloture
{
    public function __construct(
        public readonly bool $exerciceActif,
        public readonly bool $exerciceCloture,
        public readonly bool $budgetsVerrouilles,
        public readonly bool $operationsEquilibrees,
        public readonly bool $regularisationsGenerees,
        public readonly bool $anouveauxGeneres,
        public readonly bool $clotureComptesGestionGeneree,
        public readonly bool $exerciceSuivantExiste,
        public readonly ?Exercice $exerciceSuivant,
        public readonly array $erreurs = [],
    ) {}

    public function peutGenererClotureComptesGestion(): bool
    {
        return
            $this->exerciceActif
            && !$this->exerciceCloture
            && $this->regularisationsGenerees
            && !$this->clotureComptesGestionGeneree;
    }

    public function peutGenererRegularisations(): bool
    {
        return
            $this->exerciceActif
            && !$this->exerciceCloture
            && $this->budgetsVerrouilles
            && $this->operationsEquilibrees
            && !$this->regularisationsGenerees;
    }

    public function peutCalculerSoldesReportables(): bool
    {
        return
            $this->exerciceActif
            && !$this->exerciceCloture
            && $this->regularisationsGenerees;
    }

    public function peutGenererANouveaux(): bool
    {
        return
            $this->exerciceActif
            && !$this->exerciceCloture
            && $this->regularisationsGenerees
            && $this->clotureComptesGestionGeneree
            && $this->exerciceSuivantExiste
            && !$this->anouveauxGeneres;
    }


    public function peutCloturer(): bool
    {
        return
            $this->exerciceActif
            && !$this->exerciceCloture
            && $this->regularisationsGenerees
            && $this->clotureComptesGestionGeneree
            && $this->anouveauxGeneres;
    }

    public function estBloque(): bool
    {
        return $this->erreurs !== [];
    }

    public function getClasseBootstrap(): string
    {
        if ($this->exerciceCloture) {
            return 'success';
        }

        if ($this->estBloque()) {
            return 'danger';
        }

        if ($this->peutCloturer()) {
            return 'success';
        }

        return 'warning';
    }

    public function getMessage(): string
    {
        if ($this->exerciceCloture) {
            return 'L’exercice est clôturé.';
        }

        if ($this->estBloque()) {
            return 'La clôture est actuellement bloquée.';
        }

        if ($this->peutCloturer()) {
            return 'L’exercice est prêt à être clôturé.';
        }

        return 'La clôture est en cours de préparation.';
    }
}
