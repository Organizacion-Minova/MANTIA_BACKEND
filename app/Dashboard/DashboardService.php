<?php
//aislamiento de fallos, una sección del dashboard puede fallar sin afectar a las demás


namespace App\Dashboard;

use Illuminate\Support\Str;
use Throwable;

class DashboardService
{
    /** @var array<string, DashboardSection> indexadas por su clave camelCase */
    private array $sections = [];

    /** @param iterable<DashboardSection> $sections */
    public function __construct(iterable $sections)
    {
        foreach ($sections as $section) {
            $this->sections[$section->key()] = $section;
        }
    }

    /** @return array<string, SectionResult> */
    public function all(): array
    {
        return array_map(
            fn (DashboardSection $section) => $this->run($section),
            $this->sections,
        );
    }

    /** Resuelve una sola sección a partir de su slug ("daily-usage"); null si no existe. */
    public function one(string $slug): ?SectionResult
    {
        foreach ($this->sections as $key => $section) {
            if (Str::kebab($key) === $slug) {
                return $this->run($section);
            }
        }

        return null;
    }

    private function run(DashboardSection $section): SectionResult
    {
        try {
            return $section->resolve();
        } catch (Throwable $e) {
            report($e);

            return SectionResult::error();
        }
    }
}