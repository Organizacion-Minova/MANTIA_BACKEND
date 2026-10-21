<?php
// Resultado de la resolución de una sección del dashboard.
namespace App\Dashboard;

final readonly class SectionResult
{
    private function __construct(
        public SectionState $state,
        public mixed $data = null,
    ) {}

    public static function ok(mixed $data): self
    {
        return new self(SectionState::Ok, $data);
    }

    /** La fuente existe pero no hay registros; $data es la forma vacía (p. ej. []). */
    public static function empty(mixed $data): self
    {
        return new self(SectionState::Empty, $data);
    }

    public static function unavailable(): self
    {
        return new self(SectionState::Unavailable);
    }

    public static function error(): self
    {
        return new self(SectionState::Error);
    }
}