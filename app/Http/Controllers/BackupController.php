<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Copias de seguridad en JSON.
 *
 *  - Los datos van CIFRADOS (con la APP_KEY del servidor): quien abra el archivo no puede leerlos.
 *    Las copias de la versión 1 (sin cifrar) todavía se pueden restaurar.
 *  - Cada copia lleva un HASH (SHA-256) y una FIRMA (HMAC con la APP_KEY del servidor).
 *    Si alguien edita el archivo a mano, la firma deja de coincidir y no se puede restaurar.
 *  - Descargar, restaurar y eliminar piden la contraseña del usuario que hace la acción.
 *  - Al respaldar una tabla se incluyen automáticamente las tablas que dependen de ella
 *    (por ejemplo, máquinas incluye uso diario e inspecciones), para que la copia sea coherente.
 *  - Restaurar reemplaza esas tablas dentro de UNA transacción: si algo falla, no se cambia nada.
 */
class BackupController extends Controller
{
    private const DISCO = 'local';
    private const CARPETA = 'backups';
    private const FORMATO = 'mantia-backup';
    private const VERSION = 2;

    // Lo que el administrador puede elegir respaldar, con su nombre en español.
    private const TABLAS_DISPONIBLES = [
        'location' => 'Ubicaciones',
        'location_category' => 'Categorías de ubicación',
        'machine' => 'Máquinas',
        'machine_category' => 'Categorías de máquina',
        'equipment' => 'Equipos',
        'tool' => 'Herramientas',
        'category' => 'Categorías de herramientas',
        'inspection' => 'Inspecciones',
        'gas_measurement' => 'Mediciones de gases',
        'daily_use' => 'Uso diario',
        'users' => 'Usuarios',
        'roles' => 'Roles',
    ];

    private ?array $grafo = null;

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function options()
    {
        $opciones = [];
        foreach (self::TABLAS_DISPONIBLES as $tabla => $etiqueta) {
            if (Schema::hasTable($tabla)) {
                $opciones[] = [
                    'tabla' => $tabla,
                    'etiqueta' => $etiqueta,
                    'registros' => DB::table($tabla)->count(),
                ];
            }
        }

        return response()->json(['opciones' => $opciones]);
    }

    public function index()
    {
        $disco = Storage::disk(self::DISCO);

        $backups = collect($disco->files(self::CARPETA))
            ->filter(fn ($ruta) => str_ends_with($ruta, '.meta.json'))
            ->map(fn ($ruta) => json_decode($disco->get($ruta), true))
            ->filter()
            ->sortByDesc('creado_en')
            ->values();

        return response()->json(['backups' => $backups]);
    }

    // ------------------------------------------------------------------
    // Crear
    // ------------------------------------------------------------------

    public function store(Request $request)
    {
        $request->validate([
            'tablas' => 'required|array|min:1',
            'tablas.*' => 'string|in:' . implode(',', array_keys(self::TABLAS_DISPONIBLES)),
        ]);

        $incluye = $this->ordenar($this->cierre($request->tablas));

        $datos = [];
        foreach ($incluye as $tabla) {
            $datos[$tabla] = $this->leerTabla($tabla);
        }

        $payload = [
            'formato' => self::FORMATO,
            'version' => self::VERSION,
            'creado_en' => now()->toDateTimeString(),
            'creado_por' => $request->user()->name,
            'tablas' => array_values($request->tablas),
            'incluye' => $incluye,
            'cifrado' => true,
            // Los datos se guardan cifrados; el hash y la firma se calculan sobre este texto cifrado
            'contenido' => Crypt::encryptString(
                json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)
            ),
        ];

        $sellado = $this->sellar($payload);

        $sello = now()->format('Y-m-d_His');
        $disco = Storage::disk(self::DISCO);

        // Si dos copias se hacen en el mismo segundo, la segunda no pisa a la primera
        $nombreArchivo = "backup_{$sello}.json";
        $n = 2;
        while ($disco->exists(self::CARPETA . '/' . $nombreArchivo)) {
            $nombreArchivo = "backup_{$sello}-{$n}.json";
            $n++;
        }
        $ruta = self::CARPETA . '/' . $nombreArchivo;

        $disco->put($ruta, json_encode($sellado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));

        $meta = [
            'archivo' => $nombreArchivo,
            'tablas' => $payload['tablas'],
            'incluye' => $incluye,
            'creado_en' => $payload['creado_en'],
            'creado_por' => $payload['creado_por'],
            'cifrado' => true,
            'peso_kb' => round($disco->size($ruta) / 1024, 1),
            'hash' => $sellado['hash'],
        ];

        $disco->put($ruta . '.meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['message' => 'Backup generado correctamente.', 'backup' => $meta], 201);
    }

    // ------------------------------------------------------------------
    // Descargar (pide contraseña)
    // ------------------------------------------------------------------

    public function download(Request $request, $archivo)
    {
        if ($error = $this->confirmarPassword($request)) {
            return $error;
        }

        $ruta = $this->rutaSegura($archivo);
        if (! $ruta) {
            return response()->json(['message' => 'El backup no existe.'], 404);
        }

        $contenido = json_decode(Storage::disk(self::DISCO)->get($ruta), true);
        if (($contenido['formato'] ?? null) === self::FORMATO && ! $this->esIntegro($contenido)) {
            return response()->json(['message' => 'La copia fue modificada y no pasa la verificación de integridad.'], 422);
        }

        Log::info("Backup descargado: {$archivo} por usuario #{$request->user()->id}");

        return Storage::disk(self::DISCO)->download($ruta);
    }

    // ------------------------------------------------------------------
    // Eliminar (pide contraseña)
    // ------------------------------------------------------------------

    public function destroy(Request $request, $archivo)
    {
        if ($error = $this->confirmarPassword($request)) {
            return $error;
        }

        $ruta = $this->rutaSegura($archivo);
        if (! $ruta) {
            return response()->json(['message' => 'El backup no existe.'], 404);
        }

        Storage::disk(self::DISCO)->delete([$ruta, $ruta . '.meta.json']);

        Log::warning("Backup eliminado: {$archivo} por usuario #{$request->user()->id}");

        return response()->json(['message' => 'Backup eliminado correctamente.']);
    }

    // ------------------------------------------------------------------
    // Restaurar (pide contraseña)
    // ------------------------------------------------------------------

    public function restore(Request $request, $archivo)
    {
        if ($error = $this->confirmarPassword($request)) {
            return $error;
        }

        $ruta = $this->rutaSegura($archivo);
        if (! $ruta) {
            return response()->json(['message' => 'El backup no existe.'], 404);
        }

        $contenido = json_decode(Storage::disk(self::DISCO)->get($ruta), true);

        if (! is_array($contenido) || ($contenido['formato'] ?? null) !== self::FORMATO) {
            return response()->json(['message' => 'Esta copia es de un formato anterior y no se puede restaurar. Genera una nueva.'], 422);
        }

        if (! $this->esIntegro($contenido)) {
            return response()->json(['message' => 'La copia fue modificada y no pasa la verificación de integridad. No se restauró nada.'], 422);
        }

        $datos = $this->leerDatos($contenido);
        if ($datos === null) {
            return response()->json(['message' => 'No se pudo abrir la copia: se hizo con otra APP_KEY o está dañada. No se restauró nada.'], 422);
        }

        $permitidas = $this->cierre(array_keys(self::TABLAS_DISPONIBLES));

        foreach (array_keys($datos) as $tabla) {
            if (! in_array($tabla, $permitidas, true) || ! Schema::hasTable($tabla)) {
                return response()->json(['message' => "La copia incluye una tabla no permitida: {$tabla}."], 422);
            }
        }

        // Cada columna de la copia debe existir en la tabla actual
        foreach ($datos as $tabla => $filas) {
            $columnas = Schema::getColumnListing($tabla);
            foreach ($filas as $fila) {
                if (! is_array($fila) || array_diff(array_keys($fila), $columnas)) {
                    return response()->json([
                        'message' => "La estructura de {$tabla} cambió desde que se hizo la copia. No se puede restaurar.",
                    ], 422);
                }
            }
        }

        $orden = $this->ordenar(array_keys($datos));
        $yo = $request->user();
        $total = 0;

        try {
            DB::transaction(function () use ($orden, $datos, $yo, &$total) {
                foreach (array_reverse($orden) as $tabla) {
                    DB::table($tabla)->delete();
                }

                foreach ($orden as $tabla) {
                    $this->insertar($tabla, $datos[$tabla]);
                    $total += count($datos[$tabla]);
                }

                $this->reiniciarSecuencias($orden);
                $this->verificarQueSigoPudiendoEntrar($yo, $orden);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            Log::error('Error al restaurar backup: ' . $e->getMessage());

            $mensaje = $e->getCode() === '23503'
                ? 'No se pudo restaurar: hay datos actuales que dependen de esta información. No se cambió nada.'
                : 'No se pudo restaurar la copia. No se cambió nada.';

            return response()->json(['message' => $mensaje], 422);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::warning("Backup restaurado: {$archivo} por usuario #{$yo->id} ({$total} registros)");

        return response()->json([
            'message' => 'Copia restaurada correctamente.',
            'tablas' => $orden,
            'registros' => $total,
        ]);
    }

    // ------------------------------------------------------------------
    // Seguridad: contraseña, ruta, hash y firma
    // ------------------------------------------------------------------

    private function confirmarPassword(Request $request): ?JsonResponse
    {
        $request->validate(['password' => 'required|string'], [
            'password.required' => 'Escribe tu contraseña para continuar.',
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return response()->json(['message' => 'La contraseña es incorrecta.'], 422);
        }

        return null;
    }

    // Solo nombres como backup_2026-10-30_101500.json; evita salir de la carpeta de backups.
    private function rutaSegura($archivo): ?string
    {
        $nombre = basename((string) $archivo);

        if (! preg_match('/^backup_[0-9_-]+\.json$/', $nombre)) {
            return null;
        }

        $ruta = self::CARPETA . '/' . $nombre;

        return Storage::disk(self::DISCO)->exists($ruta) ? $ruta : null;
    }

    // Devuelve los datos de la copia: los descifra si es versión 2, o los lee directo si es la versión 1.
    private function leerDatos(array $archivo): ?array
    {
        if (empty($archivo['cifrado'])) {
            return is_array($archivo['datos'] ?? null) ? $archivo['datos'] : [];
        }

        try {
            $datos = json_decode(Crypt::decryptString((string) ($archivo['contenido'] ?? '')), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($datos) ? $datos : null;
    }

    private function canonico(array $contenido): string
    {
        return json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function sellar(array $payload): array
    {
        $canonico = $this->canonico($payload);

        return $payload + [
            'hash' => hash('sha256', $canonico),
            'firma' => hash_hmac('sha256', $canonico, (string) config('app.key')),
        ];
    }

    private function esIntegro(array $archivo): bool
    {
        if (! isset($archivo['hash'], $archivo['firma'])) {
            return false;
        }

        $payload = $archivo;
        unset($payload['hash'], $payload['firma']);

        try {
            $canonico = $this->canonico($payload);
        } catch (\Throwable) {
            return false;
        }

        return hash_equals((string) $archivo['hash'], hash('sha256', $canonico))
            && hash_equals((string) $archivo['firma'], hash_hmac('sha256', $canonico, (string) config('app.key')));
    }

    // ------------------------------------------------------------------
    // Dependencias entre tablas (se leen de las llaves foráneas de la base de datos)
    // ------------------------------------------------------------------

    /** @return array<string, string[]> tabla hija => tablas padre */
    private function padres(): array
    {
        if ($this->grafo !== null) {
            return $this->grafo;
        }

        // model_has_roles no tiene llave foránea, pero depende de roles y usuarios
        $grafo = ['model_has_roles' => ['roles', 'users']];

        if (DB::getDriverName() === 'pgsql') {
            $filas = DB::select(
                "select c.relname as hijo, p.relname as padre
                 from pg_constraint k
                 join pg_class c on c.oid = k.conrelid
                 join pg_class p on p.oid = k.confrelid
                 where k.contype = 'f'
                   and c.relnamespace = (select oid from pg_namespace where nspname = current_schema())"
            );

            foreach ($filas as $fila) {
                if ($fila->hijo !== $fila->padre) {
                    $grafo[$fila->hijo][] = $fila->padre;
                }
            }
        }

        return $this->grafo = $grafo;
    }

    // Las tablas elegidas + todas las que dependen de ellas.
    private function cierre(array $tablas): array
    {
        $conjunto = array_values(array_filter(array_unique($tablas), fn ($t) => Schema::hasTable($t)));

        do {
            $cambio = false;
            foreach ($this->padres() as $hijo => $padres) {
                if (! in_array($hijo, $conjunto, true) && Schema::hasTable($hijo) && array_intersect($padres, $conjunto)) {
                    $conjunto[] = $hijo;
                    $cambio = true;
                }
            }
        } while ($cambio);

        return $conjunto;
    }

    // Padres antes que hijos.
    private function ordenar(array $conjunto): array
    {
        $orden = [];
        $pendientes = array_values($conjunto);

        while ($pendientes) {
            $avanzo = false;

            foreach ($pendientes as $i => $tabla) {
                $padres = array_intersect($this->padres()[$tabla] ?? [], $conjunto);

                if (! array_diff($padres, $orden)) {
                    $orden[] = $tabla;
                    unset($pendientes[$i]);
                    $avanzo = true;
                }
            }

            if (! $avanzo) {
                return array_merge($orden, array_values($pendientes));
            }
        }

        return $orden;
    }

    // ------------------------------------------------------------------
    // Leer y escribir tablas
    // ------------------------------------------------------------------

    private function columnasBinarias(string $tabla): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return [];
        }

        return array_map(
            fn ($r) => $r->column_name,
            DB::select(
                "select column_name from information_schema.columns
                 where table_schema = current_schema() and table_name = ? and data_type = 'bytea'",
                [$tabla]
            )
        );
    }

    // Las columnas binarias (firmas) se guardan en base64 para que quepan en JSON.
    private function leerTabla(string $tabla): array
    {
        $columnas = Schema::getColumnListing($tabla);
        $binarias = $this->columnasBinarias($tabla);

        $seleccion = array_map(
            fn ($c) => in_array($c, $binarias, true) ? "encode(\"{$c}\", 'base64') as \"{$c}\"" : "\"{$c}\"",
            $columnas
        );

        $consulta = DB::table($tabla)->selectRaw(implode(', ', $seleccion));
        if (in_array('id', $columnas, true)) {
            $consulta->orderBy('id');
        }

        return json_decode(json_encode($consulta->get()), true);
    }

    private function insertar(string $tabla, array $filas): void
    {
        if (! $filas) {
            return;
        }

        $binarias = $this->columnasBinarias($tabla);

        if (! $binarias) {
            foreach (array_chunk($filas, 500) as $lote) {
                DB::table($tabla)->insert($lote);
            }

            return;
        }

        // Tablas con columnas binarias: se decodifica el base64 desde SQL
        foreach ($filas as $fila) {
            $columnas = array_keys($fila);
            $marcas = array_map(
                fn ($c) => in_array($c, $binarias, true) && $fila[$c] !== null ? "decode(?, 'base64')" : '?',
                $columnas
            );

            DB::insert(
                'insert into "' . $tabla . '" ("' . implode('", "', $columnas) . '") values (' . implode(', ', $marcas) . ')',
                array_values($fila)
            );
        }
    }

    // Después de insertar ids a mano, la secuencia de Postgres debe seguir desde el máximo.
    private function reiniciarSecuencias(array $tablas): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'id')) {
                DB::statement(
                    "select setval(pg_get_serial_sequence('\"{$tabla}\"', 'id'),
                        coalesce((select max(id) from \"{$tabla}\"), 1),
                        (select max(id) is not null from \"{$tabla}\"))"
                );
            }
        }
    }

    // Evita que una copia vieja deje al administrador que restaura sin poder entrar.
    private function verificarQueSigoPudiendoEntrar(User $yo, array $tablas): void
    {
        $tocaUsuarios = array_intersect(['users', 'roles', 'model_has_roles'], $tablas);

        if (! $tocaUsuarios) {
            return;
        }

        $miFila = DB::table('users')->where('id', $yo->id)->first();

        if (! $miFila) {
            throw new \RuntimeException('No se restauró nada: la copia no incluye tu cuenta y te quedarías sin acceso.');
        }

        // En la copia la cuenta pudo estar desactivada: tampoco se podría volver a entrar
        if (in_array($miFila->is_active ?? true, [false, 0, '0', 'f'], true)
            || in_array($miFila->status, ['inactive', 'rejected', 'pending'], true)) {
            throw new \RuntimeException('No se restauró nada: en esa copia tu cuenta está desactivada y te quedarías sin acceso.');
        }

        $esJefe = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $yo->id)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.name', 'Jefe de mina')
            ->where('roles.is_active', true)
            ->exists();

        if (! $esJefe) {
            throw new \RuntimeException('No se restauró nada: con esa copia tu cuenta dejaría de ser Jefe de mina.');
        }
    }
}