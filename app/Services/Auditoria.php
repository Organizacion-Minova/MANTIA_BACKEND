<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AiPendingAction;
use App\Models\User;
use App\Support\EsquemaBD;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use SplObjectStorage;

/**
 * Historial de cambios del sistema.
 *
 * Escucha los eventos de Eloquent (crear, editar, eliminar) de todos los modelos de la aplicación
 * y guarda quién hizo el cambio, su rol, la fecha y los valores antes/después.
 * Solo se registra lo que hace un usuario con sesión iniciada (no los seeders ni las tareas programadas).
 *
 * El Superadministrador puede revertir un cambio: se aplica la operación contraria y queda
 * registrada como un cambio nuevo enlazado al original (como "git revert").
 */
class Auditoria
{
    /** Columnas que nunca se guardan en el historial */
    private const OCULTAS = ['password', 'remember_token', 'verification_expires_at'];

    /** Columnas que no cuentan como cambio */
    private const IGNORADAS = ['created_at', 'updated_at', 'email_verified_at'];

    /**
     * Modelos que no se auditan: las respuestas y firmas de un formulario son parte de su inspección
     * (si se elimina la inspección, se guardan junto con ella y vuelven al revertir).
     */
    private const EXCLUIDOS = [
        ActivityLog::class,
        AiPendingAction::class,
        \App\Models\InspectionAnswerEquipment::class,
        \App\Models\InspectionAnswerMachine::class,
        \App\Models\PreoperationalAnswerMachine::class,
        \App\Models\InspectionSignatureEquipment::class,
    ];

    public const MODULOS = [
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'category_group' => 'Grupos de herramientas',
        'category' => 'Categorías de herramientas',
        'machine_category' => 'Categorías de máquina',
        'location_category' => 'Categorías de ubicación',
        'location' => 'Ubicaciones',
        'equipment' => 'Equipos',
        'machine' => 'Máquinas',
        'tool' => 'Herramientas',
        'tool_stock_condition' => 'Inventario de herramientas',
        'tools_machine' => 'Consumibles de máquinas',
        'gas_measurement' => 'Mediciones de gases',
        'gas_measurement_detail' => 'Mediciones de gases',
        'daily_use' => 'Uso diario',
        'inspection' => 'Inspecciones',
        'inspection_format_machine' => 'Formatos de máquinas',
        'inspection_section_machine' => 'Formatos de máquinas',
        'inspection_question_machine' => 'Formatos de máquinas',
        'inspection_machine' => 'Inspecciones de máquinas',
        'inspection_answer_machine' => 'Inspecciones de máquinas',
        'preoperational_machine' => 'Preoperacionales',
        'preoperational_answer_machine' => 'Preoperacionales',
        'inspection_format_equipment' => 'Formatos de equipos',
        'inspection_section_equipment' => 'Formatos de equipos',
        'inspection_question_equipment' => 'Formatos de equipos',
        'inspection_equipment' => 'Inspecciones de equipos',
        'inspection_answer_equipment' => 'Inspecciones de equipos',
        'inspection_signature_equipment' => 'Inspecciones de equipos',
        'backups' => 'Copias de seguridad',
        'tests' => 'Pruebas automatizadas',
    ];

    private const ACCIONES = [
        'created' => 'Creó',
        'updated' => 'Editó',
        'deleted' => 'Eliminó',
    ];

    private static bool $pausada = false;
    private static ?int $revirtiendo = null;
    private static ?SplObjectStorage $pendientesBorrar = null;

    // ------------------------------------------------------------------
    // Registro automático
    // ------------------------------------------------------------------

    public static function escucharEventos(): void
    {
        Event::listen('eloquent.created: *', fn (string $evento, array $datos) => self::alCrear($datos[0] ?? null));
        Event::listen('eloquent.updated: *', fn (string $evento, array $datos) => self::alEditar($datos[0] ?? null));
        Event::listen('eloquent.deleting: *', fn (string $evento, array $datos) => self::antesDeEliminar($datos[0] ?? null));
        Event::listen('eloquent.deleted: *', fn (string $evento, array $datos) => self::alEliminar($datos[0] ?? null));
    }

    /** Ejecuta algo sin dejar rastro en el historial (por ejemplo, al restaurar una copia). */
    public static function sinRegistrar(callable $accion): mixed
    {
        $antes = self::$pausada;
        self::$pausada = true;

        try {
            return $accion();
        } finally {
            self::$pausada = $antes;
        }
    }

    private static function auditable(mixed $modelo): bool
    {
        if (self::$pausada || ! $modelo instanceof Model) {
            return false;
        }

        if (in_array($modelo::class, self::EXCLUIDOS, true)) {
            return false;
        }

        $esDeLaApp = str_starts_with($modelo::class, 'App\\Models\\') || $modelo instanceof Role;

        return $esDeLaApp && self::actor() !== null && self::tablaLista();
    }

    private static function tablaLista(): bool
    {
        static $existe = null;

        return $existe ??= Schema::hasTable('activity_logs');
    }

    /** Usuario que está haciendo el cambio (solo peticiones con sesión iniciada). */
    public static function actor(): ?User
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $usuario = Auth::user();

        return $usuario instanceof User ? $usuario : null;
    }

    private static function alCrear(mixed $modelo): void
    {
        if (! self::auditable($modelo)) {
            return;
        }

        $valores = self::limpiar($modelo->getAttributes());

        self::guardar($modelo, 'created', null, $valores, revertible: self::sePuedeRevertir($modelo, 'created'));
    }

    private static function alEditar(mixed $modelo): void
    {
        if (! self::auditable($modelo)) {
            return;
        }

        $cambios = array_diff_key($modelo->getChanges(), array_flip(self::IGNORADAS));
        $cambios = self::limpiar($cambios);

        if (! $cambios) {
            return;
        }

        $antes = [];
        foreach (array_keys($cambios) as $campo) {
            $antes[$campo] = $modelo->getRawOriginal($campo);
        }
        $antes = self::limpiar($antes);

        self::guardar($modelo, 'updated', $antes, $cambios, revertible: self::sePuedeRevertir($modelo, 'updated'));
    }

    private static function antesDeEliminar(mixed $modelo): void
    {
        if (! self::auditable($modelo) || ! $modelo->getKey()) {
            return;
        }

        self::$pendientesBorrar ??= new SplObjectStorage();

        try {
            self::$pendientesBorrar[$modelo] = EsquemaBD::dependientes($modelo->getTable(), $modelo->getKey());
        } catch (\Throwable $e) {
            report($e);
            self::$pendientesBorrar[$modelo] = ['filas' => [], 'completo' => false];
        }
    }

    private static function alEliminar(mixed $modelo): void
    {
        if (! self::auditable($modelo)) {
            return;
        }

        $dependientes = ['filas' => [], 'completo' => true];
        if (self::$pendientesBorrar && self::$pendientesBorrar->contains($modelo)) {
            $dependientes = self::$pendientesBorrar[$modelo];
            self::$pendientesBorrar->detach($modelo);
        }

        $valores = self::limpiar($modelo->getRawOriginal() ?: $modelo->getAttributes());
        $extra = $dependientes['filas'] ? ['dependientes' => $dependientes['filas']] : null;

        self::guardar(
            $modelo,
            'deleted',
            $valores,
            null,
            extra: $extra,
            revertible: $dependientes['completo'] && self::sePuedeRevertir($modelo, 'deleted'),
        );
    }

    private static function sePuedeRevertir(Model $modelo, string $accion): bool
    {
        // Crear o borrar cuentas no se revierte desde aquí: se maneja en el panel de usuarios
        if ($modelo instanceof User && $accion !== 'updated') {
            return false;
        }

        return $modelo->getKeyName() === 'id';
    }

    /** Quita columnas ocultas y convierte binarios a base64. */
    private static function limpiar(array $valores): array
    {
        $valores = array_diff_key($valores, array_flip(self::OCULTAS));

        return EsquemaBD::serializarFila($valores);
    }

    private static function guardar(
        Model $modelo,
        string $accion,
        ?array $antes,
        ?array $despues,
        ?array $extra = null,
        bool $revertible = false,
    ): void {
        $tabla = $modelo->getTable();
        $modulo = self::MODULOS[$tabla] ?? ucfirst(str_replace('_', ' ', $tabla));
        $verbo = self::ACCIONES[$accion] ?? $accion;
        $nombre = self::etiqueta($modelo, $antes ?? [], $despues ?? []);

        $descripcion = "{$verbo} en {$modulo}: {$nombre}";
        if ($accion === 'updated' && $despues) {
            $descripcion .= ' (' . implode(', ', array_map(fn ($c) => self::nombreCampo($c), array_keys($despues))) . ')';
        }

        if (self::$revirtiendo) {
            $descripcion = 'Reversión — ' . $descripcion;
        }

        self::escribir([
            'action' => $accion,
            'module' => $modulo,
            'auditable_type' => $modelo::class,
            'auditable_id' => (string) $modelo->getKey(),
            'description' => mb_substr($descripcion, 0, 500),
            'old_values' => $antes,
            'new_values' => $despues,
            'extra' => $extra,
            'revertible' => $revertible && ! self::$revirtiendo,
            'revert_of' => self::$revirtiendo,
        ]);
    }

    /**
     * Registra una acción que no es un cambio de modelo (copias de seguridad, cambio de rol, pruebas...).
     */
    public static function registrar(string $accion, string $modulo, string $descripcion, ?array $antes = null, ?array $despues = null, ?User $actor = null): void
    {
        if (self::$pausada || ! self::tablaLista()) {
            return;
        }

        self::escribir([
            'action' => $accion,
            'module' => $modulo,
            'description' => mb_substr($descripcion, 0, 500),
            'old_values' => $antes,
            'new_values' => $despues,
            'revertible' => false,
            'revert_of' => self::$revirtiendo,
        ], $actor);
    }

    private static function escribir(array $datos, ?User $actor = null): void
    {
        $actor ??= self::actor();

        try {
            // Sin pasar por Eloquent para no disparar los propios eventos de auditoría
            DB::table('activity_logs')->insert(array_merge($datos, [
                'user_id' => $actor?->id,
                'user_name' => $actor ? trim($actor->name . ' ' . $actor->last_name) : 'Sistema',
                'user_role' => Roles::principal($actor) ?? 'Sin rol',
                'old_values' => isset($datos['old_values']) ? json_encode($datos['old_values'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null,
                'new_values' => isset($datos['new_values']) ? json_encode($datos['new_values'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null,
                'extra' => isset($datos['extra']) ? json_encode($datos['extra'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
                'created_at' => now(),
            ]));
        } catch (\Throwable $e) {
            // El historial nunca debe impedir que el usuario guarde su trabajo
            Log::error('No se pudo guardar el historial: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Textos legibles
    // ------------------------------------------------------------------

    private const PADRES = [
        'tool_id' => ['tool', 'Herramienta'],
        'machine_id' => ['machine', 'Máquina'],
        'equipment_id' => ['equipment', 'Equipo'],
        'location_id' => ['location', 'Ubicación'],
    ];

    private static function etiqueta(Model $modelo, array $antes, array $despues): string
    {
        $datos = array_merge($antes, $despues, array_filter($modelo->getAttributes(), fn ($v) => ! is_resource($v)));

        if ($modelo instanceof User) {
            return trim(($datos['name'] ?? '') . ' ' . ($datos['last_name'] ?? '')) ?: "#{$modelo->getKey()}";
        }

        foreach (['name', 'code', 'title', 'question', 'condition'] as $campo) {
            if (! empty($datos[$campo]) && is_scalar($datos[$campo])) {
                $texto = (string) $datos[$campo];
                $padre = self::nombrePadre($datos);

                return $padre ? "{$padre} — {$texto}" : "«{$texto}»";
            }
        }

        return self::nombrePadre($datos) ?? "registro #{$modelo->getKey()}";
    }

    private static function nombrePadre(array $datos): ?string
    {
        foreach (self::PADRES as $columna => [$tabla, $etiqueta]) {
            if (! empty($datos[$columna])) {
                try {
                    $nombre = DB::table($tabla)->where('id', $datos[$columna])->value('name');
                } catch (\Throwable) {
                    $nombre = null;
                }

                return $nombre ? "{$etiqueta} «{$nombre}»" : null;
            }
        }

        return null;
    }

    public const CAMPOS = [
        'name' => 'Nombre', 'last_name' => 'Apellido', 'code' => 'Código', 'email' => 'Correo',
        'status' => 'Estado', 'is_active' => 'Activo', 'stock' => 'Cantidad', 'condition' => 'Condición',
        'location_id' => 'Ubicación', 'category_id' => 'Categoría', 'machine_id' => 'Máquina',
        'equipment_id' => 'Equipo', 'tool_id' => 'Herramienta', 'machine_category_id' => 'Categoría de máquina',
        'location_category_id' => 'Categoría de ubicación', 'category_group_id' => 'Grupo',
        'responsible' => 'Responsable', 'model' => 'Modelo', 'brand' => 'Marca', 'serial_number' => 'Serie',
        'plate' => 'Placa', 'description' => 'Descripción', 'observations' => 'Observaciones',
        'document_type' => 'Tipo de documento', 'document_number' => 'Número de documento',
        'photo_path' => 'Foto', 'image' => 'Imagen', 'quantity' => 'Cantidad', 'date' => 'Fecha',
        'guard_name' => 'Guard', 'characteristics' => 'Características', 'role' => 'Rol', 'roles' => 'Roles',
    ];

    public static function nombreCampo(string $campo): string
    {
        return self::CAMPOS[$campo] ?? str_replace('_', ' ', $campo);
    }

    // ------------------------------------------------------------------
    // Revertir
    // ------------------------------------------------------------------

    /**
     * Aplica la operación contraria de un cambio. Lanza RuntimeException con un mensaje para el usuario
     * si no se puede revertir sin dañar datos más recientes.
     */
    public static function revertir(ActivityLog $log, User $admin, bool $forzar = false): string
    {
        if (! $log->revertible || ! $log->auditable_type || ! class_exists($log->auditable_type)) {
            throw new RuntimeException('Este cambio no se puede revertir.');
        }

        if ($log->reverted_at) {
            throw new RuntimeException('Este cambio ya fue revertido.');
        }

        if ($log->revert_of) {
            throw new RuntimeException('Este cambio es una reversión: si quieres deshacerlo, vuelve a hacer el cambio a mano.');
        }

        $clase = $log->auditable_type;
        /** @var Model $plantilla */
        $plantilla = new $clase();

        if (! $plantilla instanceof Model) {
            throw new RuntimeException('Este cambio no se puede revertir.');
        }

        $id = $log->auditable_id;

        try {
            return DB::transaction(function () use ($log, $clase, $plantilla, $id, $admin, $forzar) {
                self::$revirtiendo = $log->id;

                try {
                    $mensaje = match ($log->action) {
                        'created' => self::revertirCreacion($clase, $id, $log, $forzar),
                        'updated' => self::revertirEdicion($clase, $id, $log, $forzar),
                        'deleted' => self::revertirEliminacion($plantilla, $log),
                        default => throw new RuntimeException('Este cambio no se puede revertir.'),
                    };
                } finally {
                    self::$revirtiendo = null;
                }

                DB::table('activity_logs')->where('id', $log->id)->update([
                    'reverted_at' => now(),
                    'reverted_by' => $admin->id,
                ]);

                return $mensaje;
            });
        } catch (QueryException $e) {
            Log::warning('No se pudo revertir el cambio #' . $log->id . ': ' . $e->getMessage());

            throw new RuntimeException(match ((string) $e->getCode()) {
                '23505' => 'No se pudo revertir: ya existe otro registro con el mismo nombre o código.',
                '23503' => 'No se pudo revertir: hay otros datos que dependen de este registro o el registro del que dependía ya no existe.',
                default => 'No se pudo revertir el cambio. No se modificó nada.',
            });
        }
    }

    private static function revertirCreacion(string $clase, string $id, ActivityLog $log, bool $forzar): string
    {
        $registro = $clase::find($id);

        if (! $registro) {
            throw new RuntimeException('El registro ya no existe: alguien lo eliminó después.');
        }

        self::verificarSinCambiosPosteriores($registro, $log->new_values ?? [], $forzar);

        $registro->delete();

        return 'Se eliminó el registro creado.';
    }

    private static function revertirEdicion(string $clase, string $id, ActivityLog $log, bool $forzar): string
    {
        $registro = $clase::find($id);

        if (! $registro) {
            throw new RuntimeException('El registro ya no existe: alguien lo eliminó después. Revierte primero esa eliminación.');
        }

        self::verificarSinCambiosPosteriores($registro, $log->new_values ?? [], $forzar);

        $anteriores = self::deserializar($log->old_values ?? []);
        $registro->forceFill($anteriores)->save();

        return 'Se devolvieron los valores anteriores.';
    }

    private static function revertirEliminacion(Model $plantilla, ActivityLog $log): string
    {
        $tabla = $plantilla->getTable();
        $fila = $log->old_values ?? [];

        if (DB::table($tabla)->where($plantilla->getKeyName(), $log->auditable_id)->exists()) {
            throw new RuntimeException('Ya existe un registro con ese identificador: no se puede recuperar.');
        }

        // Solo columnas que todavía existen en la tabla
        $columnas = Schema::getColumnListing($tabla);
        $fila = array_intersect_key($fila, array_flip($columnas));

        EsquemaBD::insertarFila($tabla, $fila);

        // Las filas que dependían de él (condiciones de stock, respuestas, firmas...)
        $recuperadas = 0;
        foreach ($log->extra['dependientes'] ?? [] as $tablaHija => $filas) {
            if (! Schema::hasTable($tablaHija)) {
                continue;
            }
            $columnasHija = Schema::getColumnListing($tablaHija);

            foreach ($filas as $filaHija) {
                if (isset($filaHija['id']) && DB::table($tablaHija)->where('id', $filaHija['id'])->exists()) {
                    continue;
                }
                EsquemaBD::insertarFila($tablaHija, array_intersect_key($filaHija, array_flip($columnasHija)));
                $recuperadas++;
            }
        }

        // El insert directo no pasa por Eloquent: se deja constancia a mano
        $modulo = self::MODULOS[$tabla] ?? $tabla;
        self::escribir([
            'action' => 'created',
            'module' => $modulo,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'description' => mb_substr("Reversión — Recuperó en {$modulo} lo eliminado: " . preg_replace('/^Eliminó en [^:]+: /u', '', $log->description), 0, 500),
            'old_values' => null,
            'new_values' => $log->old_values,
            'revertible' => false,
            'revert_of' => $log->id,
        ]);

        return $recuperadas
            ? "Se recuperó el registro y {$recuperadas} dato(s) relacionado(s)."
            : 'Se recuperó el registro.';
    }

    /** Si el registro cambió después, revertir pisaría ese trabajo: se avisa en vez de hacerlo. */
    private static function verificarSinCambiosPosteriores(Model $registro, array $esperados, bool $forzar): void
    {
        if ($forzar) {
            return;
        }

        $actuales = self::limpiar($registro->getAttributes());

        foreach (array_diff_key($esperados, array_flip(self::IGNORADAS)) as $campo => $valor) {
            if (! array_key_exists($campo, $actuales)) {
                continue;
            }

            if (self::normalizar($actuales[$campo]) !== self::normalizar($valor)) {
                throw new RuntimeException(
                    'Este registro cambió después (' . self::nombreCampo($campo) . '). Revierte primero los cambios más recientes o confirma para forzar.',
                    409
                );
            }
        }
    }

    private static function normalizar(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_array($valor)) {
            return json_encode($valor);
        }
        if (is_numeric($valor)) {
            return (string) (0 + $valor);
        }

        return trim((string) $valor);
    }

    private static function deserializar(array $valores): array
    {
        foreach ($valores as $campo => $valor) {
            if (is_array($valor) && array_key_exists('__base64__', $valor)) {
                $valores[$campo] = base64_decode($valor['__base64__']);
            }
        }

        return $valores;
    }
}
