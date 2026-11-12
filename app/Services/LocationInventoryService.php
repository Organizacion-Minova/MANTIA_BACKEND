<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\GasMeasurementDetail;
use App\Models\Location;
use App\Models\Machine;
use App\Models\Tools;
use App\Models\User;

/**
 * Inventario de una ubicación: qué máquinas, equipos, herramientas y mediciones de gases tiene.
 * Lo usan la ficha de la ubicación (JSON) y las exportaciones a Excel y PDF.
 *
 * Cada sección solo se incluye si el usuario tiene el permiso del módulo correspondiente
 * (machine.view, equipment.view, tool.view, gas-measurements.view); si no, queda en null.
 */
class LocationInventoryService
{
    /** Cuántas mediciones de gases recientes se listan en la ficha (el Excel y el PDF incluyen más). */
    private const ULTIMAS_MEDICIONES = 5;

    /** Estados guardados (en español o inglés) => texto que ve el usuario. */
    private const ESTADOS = [
        'active' => 'Activo',
        'activo' => 'Activo',
        'inactive' => 'Inactivo',
        'inactivo' => 'Inactivo',
        'maintenance' => 'Mantenimiento',
        'mantenimiento' => 'Mantenimiento',
    ];

    private const GASES = [
        'oxygen' => 'Oxígeno',
        'methane' => 'Metano',
        'carbon_dioxide' => 'CO2',
        'hydrogen_sulfide' => 'H2S',
        'carbon_monoxide' => 'CO',
        'nitrogen_dioxide' => 'NO2',
    ];

    /**
     * @param  int  $maxMedicionesGas  cuántas mediciones de gases recientes se incluyen
     */
    public function obtener(User $usuario, Location $ubicacion, int $maxMedicionesGas = self::ULTIMAS_MEDICIONES): array
    {
        $ubicacion->loadMissing('locationCategory');

        $maquinas = $usuario->can('machine.view') ? $this->maquinas($ubicacion->id) : null;
        $equipos = $usuario->can('equipment.view') ? $this->equipos($ubicacion->id) : null;
        $herramientas = $usuario->can('tool.view') ? $this->herramientas($ubicacion->id) : null;
        $gases = $usuario->can('gas-measurements.view') ? $this->gases($ubicacion->id, $maxMedicionesGas) : null;

        $elementosFisicos = collect($maquinas ?? [])->merge($equipos ?? []);

        return [
            'ubicacion' => [
                'id' => $ubicacion->id,
                'nombre' => $ubicacion->name,
                'descripcion' => $ubicacion->description,
                'estado' => $this->estado($ubicacion->status),
                'categoria' => $ubicacion->locationCategory?->name,
            ],
            'resumen' => [
                'maquinas' => $maquinas === null ? null : count($maquinas),
                'equipos' => $equipos === null ? null : count($equipos),
                'herramientas' => $herramientas === null ? null : count($herramientas),
                'mediciones_gases' => $gases === null ? null : $gases['total'],
                'activos' => $elementosFisicos->where('estado', 'Activo')->count(),
                'en_mantenimiento' => $elementosFisicos->where('estado', 'Mantenimiento')->count(),
                'inactivos' => $elementosFisicos->where('estado', 'Inactivo')->count(),
                'stock_bajo' => $herramientas === null ? null : collect($herramientas)->where('stock_bajo', true)->count(),
            ],
            'maquinas' => $maquinas,
            'equipos' => $equipos,
            'herramientas' => $herramientas,
            'gases' => $gases === null ? null : $gases['ultimas'],
        ];
    }

    private function maquinas(int $ubicacionId): array
    {
        return Machine::with('category_maquina:id,name')
            ->where('location_id', $ubicacionId)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status', 'responsible', 'machine_category_id'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'codigo' => $m->code,
                'nombre' => $m->name,
                'estado' => $this->estado($m->status),
                'responsable' => $m->responsible,
                'categoria' => $m->category_maquina?->name,
            ])
            ->values()
            ->all();
    }

    private function equipos(int $ubicacionId): array
    {
        return Equipment::where('location_id', $ubicacionId)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status', 'responsible'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'codigo' => $e->code,
                'nombre' => $e->name,
                'estado' => $this->estado($e->status),
                'responsable' => $e->responsible,
            ])
            ->values()
            ->all();
    }

    private function herramientas(int $ubicacionId): array
    {
        $minimo = (int) config('alertas.stock_minimo_herramienta');

        return Tools::with(['categoria:id,name,category_group_id', 'condiciones'])
            ->where('location_id', $ubicacionId)
            ->orderBy('name')
            ->get()
            ->map(function ($h) use ($minimo) {
                $stock = $h->condiciones->pluck('stock', 'condition');
                $total = (int) $h->stock_total;

                return [
                    'id' => $h->id,
                    'nombre' => $h->name,
                    'categoria' => $h->categoria?->name,
                    'consumible' => (int) $h->categoria?->category_group_id === Tools::CONSUMABLE_GROUP,
                    'stock_total' => $total,
                    'bueno' => (int) ($stock['bueno'] ?? 0),
                    'regular' => (int) ($stock['regular'] ?? 0),
                    'malo' => (int) ($stock['malo'] ?? 0),
                    'stock_bajo' => $total <= $minimo,
                ];
            })
            ->values()
            ->all();
    }

    /** Total de veces que se midió la ubicación y las últimas mediciones (con los gases fuera de rango). */
    private function gases(int $ubicacionId, int $maximo): array
    {
        $total = GasMeasurementDetail::where('location_id', $ubicacionId)->count();

        $ultimas = GasMeasurementDetail::query()
            ->join('gas_measurement as g', 'g.id', '=', 'gas_measurement_detail.gas_measurement_id')
            ->where('gas_measurement_detail.location_id', $ubicacionId)
            ->orderByDesc('g.measurement_date')
            ->orderByDesc('g.measurement_time')
            ->limit($maximo)
            ->get(['gas_measurement_detail.*', 'g.measurement_date', 'g.measurement_time'])
            ->map(function ($d) {
                $fuera = $this->fueraDeRango($d);

                return [
                    'id' => $d->gas_measurement_id,
                    'fecha' => $d->measurement_date,
                    'hora' => $d->measurement_time ? substr((string) $d->measurement_time, 0, 5) : null,
                    'responsable' => $d->responsible_name,
                    'fuera_de_rango' => $fuera,
                ];
            })
            ->values()
            ->all();

        return ['total' => $total, 'ultimas' => $ultimas];
    }

    /** Nombres de los gases cuyo valor está fuera de los límites de config/alertas.php. */
    private function fueraDeRango(GasMeasurementDetail $detalle): array
    {
        $limites = config('alertas.gas', []);
        $fuera = [];

        foreach (self::GASES as $campo => $nombre) {
            $valor = $detalle->{$campo};
            if ($valor === null || $valor === '') {
                continue;
            }

            $valor = (float) $valor;
            $limite = $limites[$campo] ?? [];

            if ((isset($limite['min']) && $valor < $limite['min']) || (isset($limite['max']) && $valor > $limite['max'])) {
                $fuera[] = $nombre;
            }
        }

        return $fuera;
    }

    private function estado(?string $estado): string
    {
        return self::ESTADOS[strtolower((string) $estado)] ?? (string) $estado;
    }
}