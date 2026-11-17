<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\Auditoria;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Historial de cambios (vista tipo ramas de GitHub).
 *  - Superadministrador: ve todo y puede revertir.
 *  - Jefe de mina: ve todo MENOS lo que hacen el Superadministrador y los Visitantes. No revierte.
 */
class ActivityLogController extends Controller
{
    private const ACCIONES = [
        'created' => 'Creación',
        'updated' => 'Edición',
        'deleted' => 'Eliminación',
        'role_changed' => 'Cambio de rol',
        'password_reset' => 'Cambio de contraseña',
        'user_created' => 'Usuario creado',
        'user_status' => 'Activación de usuario',
        'account_request' => 'Solicitud de cuenta',
        'backup_created' => 'Backup generado',
        'backup_restored' => 'Backup restaurado',
        'backup_deleted' => 'Backup eliminado',
        'backup_downloaded' => 'Backup descargado',
        'backup_key' => 'Clave de backups',
        'tests_run' => 'Pruebas automatizadas',
    ];

    private function consultaVisible(Request $request): Builder
    {
        $consulta = ActivityLog::query();

        if (! Roles::esSuperadmin($request->user())) {
            $consulta->where(function ($q) {
                $q->whereNotIn('user_role', Roles::OCULTOS_PARA_JEFE)->orWhereNull('user_role');
            });
        }

        return $consulta;
    }

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'usuario' => 'nullable|integer|min:1',
            'rol' => 'nullable|string|max:60',
            'modulo' => 'nullable|string|max:60',
            'accion' => 'nullable|string|max:40',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'buscar' => 'nullable|string|max:100',
            'pagina' => 'nullable|integer|min:1|max:100000',
        ], [
            'desde.date_format' => 'La fecha inicial no es válida.',
            'hasta.date_format' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'buscar.max' => 'La búsqueda no puede superar los 100 caracteres.',
        ]);

        $consulta = $this->consultaVisible($request)
            ->when($filtros['usuario'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filtros['rol'] ?? null, fn ($q, $v) => $q->where('user_role', $v))
            ->when($filtros['modulo'] ?? null, fn ($q, $v) => $q->where('module', $v))
            ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v . ' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->when($filtros['buscar'] ?? null, function ($q, $v) {
                $texto = '%' . mb_strtolower(addcslashes($v, '%_\\')) . '%';
                $q->where(fn ($w) => $w->whereRaw('lower(description) like ?', [$texto])
                    ->orWhereRaw('lower(user_name) like ?', [$texto]));
            });

        $pagina = $consulta->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25, ['*'], 'pagina', $filtros['pagina'] ?? 1);

        $esSuper = Roles::esSuperadmin($request->user());

        $cambios = collect($pagina->items())->map(fn (ActivityLog $log) => $this->formatear($log, $esSuper));

        // Datos para los filtros y la leyenda de ramas
        $visibles = $this->consultaVisible($request);
        $ramas = (clone $visibles)->whereNotNull('user_id')
            ->selectRaw('user_id, max(user_name) as user_name, max(user_role) as user_role, count(*) as total, max(created_at) as ultimo')
            ->groupBy('user_id')
            ->orderByDesc('ultimo')
            ->get();

        return response()->json([
            'cambios' => $cambios,
            'paginacion' => [
                'pagina' => $pagina->currentPage(),
                'paginas' => $pagina->lastPage(),
                'total' => $pagina->total(),
            ],
            'ramas' => $ramas,
            'filtros' => [
                'modulos' => (clone $visibles)->distinct()->orderBy('module')->pluck('module'),
                'roles' => (clone $visibles)->whereNotNull('user_role')->distinct()->orderBy('user_role')->pluck('user_role'),
                'acciones' => collect(self::ACCIONES)->map(fn ($etiqueta, $valor) => ['valor' => $valor, 'etiqueta' => $etiqueta])->values(),
            ],
            'puede_revertir' => $esSuper,
        ]);
    }

    public function show(Request $request, $id)
    {
        $log = $this->consultaVisible($request)->findOrFail($id);

        return response()->json(['cambio' => $this->formatear($log, Roles::esSuperadmin($request->user()), true)]);
    }

    // Solo el Superadministrador. Pide su contraseña para confirmar.
    public function revert(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|max:64',
            'forzar' => 'nullable|boolean',
        ], [
            'password.required' => 'Escribe tu contraseña para confirmar la reversión.',
        ]);

        $limite = 'revertir:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($limite, 5)) {
            $minutos = (int) ceil(RateLimiter::availableIn($limite) / 60);

            return response()->json(['message' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s)."], 429);
        }

        if (! Hash::check($request->password, $request->user()->password)) {
            RateLimiter::hit($limite, 900);

            return response()->json([
                'message' => 'La contraseña es incorrecta.',
                'errors' => ['password' => ['La contraseña es incorrecta.']],
            ], 422);
        }
        RateLimiter::clear($limite);

        $log = ActivityLog::findOrFail($id);

        try {
            $mensaje = Auditoria::revertir($log, $request->user(), $request->boolean('forzar'));
        } catch (\RuntimeException $e) {
            $conflicto = $e->getCode() === 409;

            return response()->json([
                'message' => $e->getMessage(),
                'codigo' => $conflicto ? 'conflicto' : 'no_revertible',
            ], $conflicto ? 409 : 422);
        }

        Log::warning("Cambio #{$log->id} revertido por usuario #{$request->user()->id}");

        return response()->json(['message' => "Cambio revertido. {$mensaje}"]);
    }

    private function formatear(ActivityLog $log, bool $esSuper, bool $detalle = false): array
    {
        $campos = array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? [])));

        $diferencias = [];
        foreach ($campos as $campo) {
            $diferencias[] = [
                'campo' => $campo,
                'etiqueta' => Auditoria::nombreCampo($campo),
                'antes' => $this->mostrar($log->old_values[$campo] ?? null),
                'despues' => $this->mostrar($log->new_values[$campo] ?? null),
            ];
        }

        $datos = [
            'id' => $log->id,
            'usuario_id' => $log->user_id,
            'usuario' => $log->user_name,
            'rol' => $log->user_role,
            'accion' => $log->action,
            'accion_texto' => self::ACCIONES[$log->action] ?? $log->action,
            'modulo' => $log->module,
            'descripcion' => $log->description,
            'fecha' => $log->created_at?->toIso8601String(),
            'diferencias' => $detalle ? $diferencias : array_slice($diferencias, 0, 8),
            'total_diferencias' => count($diferencias),
            'dependientes' => array_sum(array_map('count', $log->extra['dependientes'] ?? [])),
            'revertido' => (bool) $log->reverted_at,
            'revertido_en' => $log->reverted_at?->toIso8601String(),
            'es_reversion' => (bool) $log->revert_of,
            'reversion_de' => $log->revert_of,
            'revertible' => $esSuper && $log->revertible && ! $log->reverted_at && ! $log->revert_of,
            'hash' => substr(hash('sha1', $log->id . '|' . $log->created_at), 0, 7),
        ];

        if ($esSuper) {
            $datos['ip'] = $log->ip;
        }

        return $datos;
    }

    private function mostrar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (is_array($valor)) {
            return array_key_exists('__base64__', $valor) ? '[archivo binario]' : json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        $texto = (string) $valor;

        return mb_strlen($texto) > 300 ? mb_substr($texto, 0, 300) . '…' : $texto;
    }
}
