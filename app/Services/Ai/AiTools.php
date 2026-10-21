<?php

namespace App\Services\Ai;

use App\Models\Tools;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Herramientas de SOLO LECTURA que la IA puede usar para consultar datos de MANTIA.
 * Ninguna escribe ni borra, y ninguna expone contraseñas, correos de usuarios ni firmas.
 * Cada una devuelve como máximo 20 registros para controlar el costo y el tamaño de la respuesta.
 */
class AiTools
{
    private const LIMITE_MAXIMO = 20;

    /** Descripción de cada herramienta, tal como la ve el modelo. */
    public function definitions(): array
    {
        $sinParametros = ['type' => 'object', 'properties' => new \stdClass()];
        $limite = ['type' => 'integer', 'description' => 'Cantidad máxima de resultados (1 a 20).'];

        return [
            [
                'name' => 'general_summary',
                'description' => 'Conteos generales de MANTIA: empresas, ubicaciones, herramientas, máquinas y equipos por estado, inspecciones y mediciones de gas. Úsala para preguntas generales como "¿cómo está todo?".',
                'input_schema' => $sinParametros,
            ],
            [
                'name' => 'alerts_summary',
                'description' => 'Alertas del sistema del usuario actual: cuántas hay sin atender, conteo por nivel y las más recientes. Niveles: danger (grave), warning (importante), info (informativa).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'solo_sin_leer' => ['type' => 'boolean', 'description' => 'true para ver solo las alertas sin atender.'],
                        'nivel' => ['type' => 'string', 'enum' => ['danger', 'warning', 'info'], 'description' => 'Filtrar por nivel.'],
                        'limite' => $limite,
                    ],
                ],
            ],
            [
                'name' => 'gas_measurements',
                'description' => 'Últimas mediciones de gases (oxígeno, metano, CO2, CO, H2S, NO2) con los límites permitidos y cuáles gases están fuera de rango.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ubicacion' => ['type' => 'string', 'description' => 'Texto para filtrar por ubicación (ej: "túnel 1").'],
                        'solo_fuera_de_rango' => ['type' => 'boolean', 'description' => 'true para mostrar solo mediciones con algún gas fuera de rango.'],
                        'limite' => $limite,
                    ],
                ],
            ],
            [
                'name' => 'tools_inventory',
                'description' => 'Inventario de herramientas: stock total y cantidad en estado bueno, regular y malo, con su categoría y ubicación. Indica cuáles tienen poco stock.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'buscar' => ['type' => 'string', 'description' => 'Texto para buscar por nombre de herramienta.'],
                        'solo_stock_bajo' => ['type' => 'boolean', 'description' => 'true para mostrar solo las de poco stock.'],
                        'limite' => $limite,
                    ],
                ],
            ],
            [
                'name' => 'machines_status',
                'description' => 'Máquinas registradas con su estado (active, inactive, maintenance), si están en operación, responsable, categoría y ubicación. Incluye el conteo por estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['active', 'inactive', 'maintenance'], 'description' => 'Filtrar por estado.'],
                        'buscar' => ['type' => 'string', 'description' => 'Texto para buscar por nombre o código.'],
                        'limite' => $limite,
                    ],
                ],
            ],
            [
                'name' => 'equipment_status',
                'description' => 'Equipos registrados con su estado (active, inactive, maintenance), responsable y ubicación. Incluye el conteo por estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['active', 'inactive', 'maintenance'], 'description' => 'Filtrar por estado.'],
                        'buscar' => ['type' => 'string', 'description' => 'Texto para buscar por nombre o código.'],
                        'limite' => $limite,
                    ],
                ],
            ],
            [
                'name' => 'recent_inspections',
                'description' => 'Inspecciones más recientes: fecha, área, equipo, quién revisó y observaciones.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'equipo' => ['type' => 'string', 'description' => 'Texto para filtrar por nombre del equipo o máquina inspeccionada.'],
                        'limite' => $limite,
                    ],
                ],
            ],
        ];
    }

    /** Ejecuta una herramienta y devuelve un arreglo listo para enviarle al modelo. */
    public function execute(string $nombre, array $entrada, ?User $usuario): array
    {
        try {
            return match ($nombre) {
                'general_summary' => $this->resumenGeneral(),
                'alerts_summary' => $this->resumenAlertas($entrada, $usuario),
                'gas_measurements' => $this->medicionesGas($entrada),
                'tools_inventory' => $this->inventarioHerramientas($entrada),
                'machines_status' => $this->estadoMaquinas($entrada),
                'equipment_status' => $this->estadoEquipos($entrada),
                'recent_inspections' => $this->inspeccionesRecientes($entrada),
                default => ['error' => "Herramienta desconocida: {$nombre}"],
            };
        } catch (Throwable $e) {
            Log::warning('AI: a tool failed', ['herramienta' => $nombre, 'error' => $e->getMessage()]);
            return ['error' => 'No se pudo consultar esa información en este momento.'];
        }
    }

    // ------------------------------------------------------------------
    // Herramientas
    // ------------------------------------------------------------------

    private function resumenGeneral(): array
    {
        return [
            'empresas' => $this->contar('company'),
            'ubicaciones' => $this->contar('location'),
            'herramientas' => $this->contar('tool'),
            'maquinas_por_estado' => $this->contarPorEstado('machine'),
            'equipos_por_estado' => $this->contarPorEstado('equipment'),
            'inspecciones_registradas' => $this->contar('inspection'),
            'mediciones_gas_registradas' => $this->contar('gas_measurement'),
            'fecha_ultima_medicion_gas' => $this->seguro(fn () => DB::table('gas_measurement')->max('measurement_date')),
        ];
    }

    private function resumenAlertas(array $in, ?User $usuario): array
    {
        if (!$usuario) {
            return ['error' => 'No hay un usuario autenticado.'];
        }

        $limite = $this->limite($in['limite'] ?? null, 8);
        $todas = $usuario->notifications()->latest()->limit(200)->get(['id', 'data', 'read_at', 'created_at']);

        $porNivel = ['danger' => 0, 'warning' => 0, 'info' => 0];
        foreach ($todas as $n) {
            $nivel = $n->data['nivel'] ?? 'info';
            $porNivel[$nivel] = ($porNivel[$nivel] ?? 0) + 1;
        }

        $lista = $todas
            ->filter(function ($n) use ($in) {
                if (!empty($in['solo_sin_leer']) && $n->read_at !== null) {
                    return false;
                }
                if (!empty($in['nivel']) && ($n->data['nivel'] ?? 'info') !== $in['nivel']) {
                    return false;
                }
                return true;
            })
            ->take($limite)
            ->map(fn ($n) => [
                'nivel' => $n->data['nivel'] ?? 'info',
                'categoria' => $n->data['tipo'] ?? null,
                'mensaje' => Str::limit((string) ($n->data['mensaje'] ?? ''), 200),
                'atendida' => $n->read_at !== null,
                'fecha' => $n->created_at?->timezone('America/Bogota')->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();

        return [
            'sin_atender' => $usuario->unreadNotifications()->count(),
            'conteo_por_nivel_en_las_ultimas_200' => $porNivel,
            'alertas' => $lista,
        ];
    }

    private function medicionesGas(array $in): array
    {
        $limite = $this->limite($in['limite'] ?? null, 5);
        $limites = config('alertas.gas');
        $soloFuera = !empty($in['solo_fuera_de_rango']);

        $consulta = DB::table('gas_measurement')->orderByDesc('id');
        if (!empty($in['ubicacion'])) {
            $consulta->whereRaw('LOWER(location) LIKE ?', ['%' . mb_strtolower((string) $in['ubicacion']) . '%']);
        }
        // Si se filtra por riesgo, se revisan más filas y luego se recorta
        $filas = $consulta->limit($soloFuera ? 200 : $limite)->get();

        $mediciones = [];
        foreach ($filas as $f) {
            $problemas = $this->gasesFueraDeRango($f, $limites);
            if ($soloFuera && empty($problemas)) {
                continue;
            }
            $mediciones[] = [
                'fecha' => $f->measurement_date,
                'hora' => $f->measurement_time,
                'ubicacion' => $f->location,
                'oxigeno' => $this->numero($f->oxygen),
                'metano' => $this->numero($f->methane),
                'dioxido_de_carbono' => $this->numero($f->carbon_dioxide),
                'monoxido_de_carbono' => $this->numero($f->carbon_monoxide),
                'acido_sulfhidrico' => $this->numero($f->hydrogen_sulfide),
                'dioxido_de_nitrogeno' => $this->numero($f->nitrogen_dioxide),
                'observacion' => Str::limit((string) $f->observation, 150),
                'gases_fuera_de_rango' => $problemas,
            ];
            if (count($mediciones) >= $limite) {
                break;
            }
        }

        return [
            'limites_permitidos' => $limites,
            'mediciones' => $mediciones,
        ];
    }

    private function inventarioHerramientas(array $in): array
    {
        $limite = $this->limite($in['limite'] ?? null, 10);
        $minimo = (int) config('alertas.stock_minimo_herramienta');

        $consulta = Tools::with(['ubicacion:id,name', 'categoria:id,name', 'condiciones'])->orderBy('name');
        if (!empty($in['buscar'])) {
            $consulta->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower((string) $in['buscar']) . '%']);
        }

        $lista = $consulta->limit(200)->get()->map(function ($h) use ($minimo) {
            $stock = $h->condiciones->pluck('stock', 'condition');
            $total = (int) $stock->sum();
            return [
                'nombre' => $h->name,
                'categoria' => $h->categoria?->name,
                'ubicacion' => $h->ubicacion?->name,
                'stock_total' => $total,
                'bueno' => (int) ($stock['bueno'] ?? 0),
                'regular' => (int) ($stock['regular'] ?? 0),
                'malo' => (int) ($stock['malo'] ?? 0),
                'stock_bajo' => $total <= $minimo,
            ];
        });

        if (!empty($in['solo_stock_bajo'])) {
            $lista = $lista->filter(fn ($h) => $h['stock_bajo']);
        }

        return [
            'stock_minimo_configurado' => $minimo,
            'total_encontradas' => $lista->count(),
            'herramientas' => $lista->take($limite)->values()->all(),
        ];
    }

    private function estadoMaquinas(array $in): array
    {
        $limite = $this->limite($in['limite'] ?? null, 10);

        $consulta = DB::table('machine as m')
            ->leftJoin('location as l', 'l.id', '=', 'm.location_id')
            ->leftJoin('machine_category as c', 'c.id', '=', 'm.machine_category_id')
            ->select('m.code', 'm.name', 'm.model', 'm.status', 'm.in_operation', 'm.responsible', 'l.name as ubicacion', 'c.name as categoria')
            ->orderBy('m.name');

        if (!empty($in['estado'])) {
            $consulta->where('m.status', $in['estado']);
        }
        if (!empty($in['buscar'])) {
            $texto = '%' . mb_strtolower((string) $in['buscar']) . '%';
            $consulta->where(fn ($q) => $q->whereRaw('LOWER(m.name) LIKE ?', [$texto])->orWhereRaw('LOWER(m.code) LIKE ?', [$texto]));
        }

        $maquinas = $consulta->limit($limite)->get()->map(fn ($m) => [
            'codigo' => $m->code,
            'nombre' => $m->name,
            'modelo' => $m->model,
            'estado' => $m->status,
            'en_operacion' => (bool) $m->in_operation,
            'responsable' => $m->responsible,
            'categoria' => $m->categoria,
            'ubicacion' => $m->ubicacion,
        ])->all();

        return [
            'conteo_por_estado' => $this->contarPorEstado('machine'),
            'maquinas' => $maquinas,
        ];
    }

    private function estadoEquipos(array $in): array
    {
        $limite = $this->limite($in['limite'] ?? null, 10);

        $consulta = DB::table('equipment as e')
            ->leftJoin('location as l', 'l.id', '=', 'e.location_id')
            ->select('e.code', 'e.name', 'e.model', 'e.status', 'e.responsible', 'l.name as ubicacion')
            ->orderBy('e.name');

        if (!empty($in['estado'])) {
            $consulta->where('e.status', $in['estado']);
        }
        if (!empty($in['buscar'])) {
            $texto = '%' . mb_strtolower((string) $in['buscar']) . '%';
            $consulta->where(fn ($q) => $q->whereRaw('LOWER(e.name) LIKE ?', [$texto])->orWhereRaw('LOWER(e.code) LIKE ?', [$texto]));
        }

        $equipos = $consulta->limit($limite)->get()->map(fn ($e) => [
            'codigo' => $e->code,
            'nombre' => $e->name,
            'modelo' => $e->model,
            'estado' => $e->status,
            'responsable' => $e->responsible,
            'ubicacion' => $e->ubicacion,
        ])->all();

        return [
            'conteo_por_estado' => $this->contarPorEstado('equipment'),
            'equipos' => $equipos,
        ];
    }

    private function inspeccionesRecientes(array $in): array
    {
        $limite = $this->limite($in['limite'] ?? null, 5);

        $consulta = DB::table('inspection')
            ->select('inspection_date', 'area', 'equipment_name', 'reviewed_by', 'observations')
            ->orderByDesc('inspection_date');

        if (!empty($in['equipo'])) {
            $consulta->whereRaw('LOWER(equipment_name) LIKE ?', ['%' . mb_strtolower((string) $in['equipo']) . '%']);
        }

        $inspecciones = $consulta->limit($limite)->get()->map(fn ($i) => [
            'fecha' => $i->inspection_date,
            'area' => $i->area,
            'equipo' => $i->equipment_name,
            'revisado_por' => $i->reviewed_by,
            'observaciones' => Str::limit((string) $i->observations, 300),
        ])->all();

        return ['inspecciones' => $inspecciones];
    }

    // ------------------------------------------------------------------
    // Ayudas
    // ------------------------------------------------------------------

    /** Mismos criterios que GasMeasurementObserver (config/alertas.php). */
    private function gasesFueraDeRango(object $f, array $limites): array
    {
        $problemas = [];

        if ($f->oxygen !== null && ($f->oxygen < $limites['oxygen']['min'] || $f->oxygen > $limites['oxygen']['max'])) {
            $problemas[] = 'oxígeno';
        }

        $nombres = [
            'methane' => 'metano',
            'carbon_dioxide' => 'dióxido de carbono',
            'carbon_monoxide' => 'monóxido de carbono',
            'hydrogen_sulfide' => 'ácido sulfhídrico',
            'nitrogen_dioxide' => 'dióxido de nitrógeno',
        ];
        foreach ($nombres as $columna => $nombre) {
            $max = $limites[$columna]['max'] ?? null;
            if ($f->$columna !== null && $max !== null && $f->$columna > $max) {
                $problemas[] = $nombre;
            }
        }

        return $problemas;
    }

    private function limite(mixed $valor, int $defecto): int
    {
        $n = (int) $valor;
        return $n > 0 ? min($n, self::LIMITE_MAXIMO) : $defecto;
    }

    private function numero(mixed $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function contar(string $tabla): ?int
    {
        return $this->seguro(fn () => DB::table($tabla)->count());
    }

    private function contarPorEstado(string $tabla): ?array
    {
        return $this->seguro(fn () => DB::table($tabla)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all());
    }

    /** Si una tabla no existe o falla, devuelve null en vez de romper todo el resumen. */
    private function seguro(callable $consulta): mixed
    {
        try {
            return $consulta();
        } catch (Throwable) {
            return null;
        }
    }
}