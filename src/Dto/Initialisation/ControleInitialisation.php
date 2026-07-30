<?php

namespace App\Dto\Initialisation;

final class ControleInitialisation
{
    public function __construct(
        private readonly string $libelle,
        private readonly bool $valide,
        private readonly ?string $message = null,
    ) {}

    public static function succes(
        string $libelle,
        ?string $message = null,
    ): self {
        return new self(
            libelle: $libelle,
            valide: true,
            message: $message,
        );
    }

    public static function erreur(
        string $libelle,
        ?string $message = null,
    ): self {
        return new self(
            libelle: $libelle,
            valide: false,
            message: $message,
        );
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
