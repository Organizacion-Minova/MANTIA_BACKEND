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

    /** Qué necesita el usuario para ver cada sección: un permiso o uno de varios roles. */
    private const ACCESO = [
        'machines'       => ['permiso' => 'machine.view'],
        'equipments'     => ['permiso' => 'equipment.view'],
        'consumables'    => ['permiso' => 'tool.view'],
        'nonConsumables' => ['permiso' => 'tool.view'],
        'dailyUsage'     => ['permiso' => 'daily-use.view'],
        'gases'          => ['permiso' => 'gas-measurements.view'],
        'locations'      => ['roles' => ['Jefe de mina', 'SST']],
        'activity'       => ['roles' => ['Jefe de mina', 'SST']],
    ];

    /** Solo las secciones que el usuario actual puede ver. @return array<string, SectionResult> */
    public function all(): array
    {
        $visibles = array_filter($this->sections, fn ($section) => $this->puedeVer($section));

        return array_map(
            fn (DashboardSection $section) => $this->run($section),
            $visibles,
        );
    }

    private function puedeVer(DashboardSection $section): bool
    {
        $usuario = request()->user();
        if (! $usuario) {
            return false;
        }

        // Una sección nueva sin regla definida queda cerrada, no abierta.
        $regla = self::ACCESO[$section->key()] ?? null;
        if (! $regla) {
            return false;
        }

        if (isset($regla['permiso'])) {
            return $usuario->can($regla['permiso']);
        }

        return $usuario->hasAnyRole($regla['roles']);
    }

    /** Resuelve una sola sección a partir de su slug ("daily-usage"); null si no existe. */
    public function one(string $slug): ?SectionResult
    {
        foreach ($this->sections as $key => $section) {
            if (Str::kebab($key) === $slug) {
                abort_unless($this->puedeVer($section), 403);
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