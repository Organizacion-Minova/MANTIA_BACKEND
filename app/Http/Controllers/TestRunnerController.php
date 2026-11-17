<?php

namespace App\Http\Controllers;

use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Botón "Correr pruebas" del Superadministrador.
 *
 * Ejecuta las pruebas automatizadas del backend (PHPUnit) en un proceso aparte.
 * SEGURIDAD: las pruebas SIEMPRE corren contra SQLite en memoria, nunca contra la base de datos real:
 *   1) aquí se fuerzan las variables de entorno del proceso,
 *   2) phpunit.xml las marca con force="true",
 *   3) tests/TestCase.php se detiene si detecta otra base de datos.
 */
class TestRunnerController extends Controller
{
    private const ARCHIVO = 'tests/ultimo-resultado.json';
    private const LIMITE_SEGUNDOS = 600;

    public function show()
    {
        $disco = Storage::disk('local');

        return response()->json([
            'disponible' => $this->phpunit() !== null,
            'resultado' => $disco->exists(self::ARCHIVO) ? json_decode($disco->get(self::ARCHIVO), true) : null,
        ]);
    }

    public function run(Request $request)
    {
        $phpunit = $this->phpunit();
        if (! $phpunit) {
            return response()->json([
                'message' => 'Las pruebas no están instaladas en este servidor (se instalan con "composer install" sin --no-dev).',
            ], 422);
        }

        $php = env('TESTS_PHP_BINARY') ?: (new PhpExecutableFinder())->find(false);
        if (! $php) {
            return response()->json(['message' => 'No se encontró el ejecutable de PHP para correr las pruebas.'], 422);
        }

        // Una sola ejecución a la vez
        $candado = Cache::lock('mantia-pruebas', self::LIMITE_SEGUNDOS + 60);
        if (! $candado->get()) {
            return response()->json(['message' => 'Ya hay pruebas corriendo. Espera a que terminen.'], 409);
        }

        try {
            @set_time_limit(self::LIMITE_SEGUNDOS + 60);

            $carpeta = storage_path('app/tests');
            if (! is_dir($carpeta)) {
                mkdir($carpeta, 0750, true);
            }
            $junit = $carpeta . '/junit.xml';
            @unlink($junit);

            $inicio = microtime(true);

            $proceso = Process::path(base_path())
                ->timeout(self::LIMITE_SEGUNDOS)
                ->env([
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'sqlite',
                    'DB_DATABASE' => ':memory:',
                    'DB_URL' => '',
                    'CACHE_STORE' => 'array',
                    'SESSION_DRIVER' => 'array',
                    'QUEUE_CONNECTION' => 'sync',
                    'MAIL_MAILER' => 'array',
                    'BROADCAST_CONNECTION' => 'null',
                    'CAPTCHA_ENABLED' => 'false',
                    'GEMINI_API_KEY' => '',
                    // Si la configuración está en caché, se ignora para que tomen efecto las variables de arriba
                    'APP_CONFIG_CACHE' => $carpeta . '/sin-cache-config.php',
                ])
                ->run([$php, $phpunit, '--log-junit', $junit, '--colors=never', '--do-not-cache-result']);

            $resultado = $this->leerJunit($junit);
            $resultado['duracion'] = round(microtime(true) - $inicio, 1);
            $resultado['ejecutado_en'] = now()->toIso8601String();
            $resultado['ejecutado_por'] = trim($request->user()->name . ' ' . $request->user()->last_name);
            $resultado['codigo_salida'] = $proceso->exitCode();

            if ($resultado['total'] === 0) {
                // No se generó el reporte: algo falló antes de empezar (se muestra el final de la salida)
                $salida = trim($proceso->output() . "\n" . $proceso->errorOutput());
                $resultado['error'] = 'Las pruebas no pudieron ejecutarse.';
                $resultado['salida'] = mb_substr($salida, -3000);
            }

            Storage::disk('local')->put(self::ARCHIVO, json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            Auditoria::registrar(
                'tests_run',
                'Pruebas automatizadas',
                "Ejecutó las pruebas: {$resultado['aprobadas']} de {$resultado['total']} aprobadas"
            );

            return response()->json(['message' => 'Pruebas terminadas.', 'resultado' => $resultado]);
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) {
            return response()->json(['message' => 'Las pruebas tardaron demasiado y se detuvieron.'], 504);
        } catch (\Throwable $e) {
            Log::error('Error al correr las pruebas: ' . $e->getMessage());

            return response()->json(['message' => 'No se pudieron ejecutar las pruebas.'], 500);
        } finally {
            $candado->release();
        }
    }

    private function phpunit(): ?string
    {
        $ruta = base_path('vendor/phpunit/phpunit/phpunit');

        return is_file($ruta) ? $ruta : null;
    }

    private function leerJunit(string $archivo): array
    {
        $vacio = ['total' => 0, 'aprobadas' => 0, 'fallidas' => 0, 'errores' => 0, 'omitidas' => 0, 'pruebas' => []];

        if (! is_file($archivo)) {
            return $vacio;
        }

        $xml = @simplexml_load_file($archivo, 'SimpleXMLElement', LIBXML_NONET);
        if (! $xml) {
            return $vacio;
        }

        $pruebas = [];
        foreach ($xml->xpath('//testcase') ?: [] as $caso) {
            $estado = 'aprobada';
            $mensaje = null;

            if (isset($caso->failure)) {
                $estado = 'fallida';
                $mensaje = (string) $caso->failure;
            } elseif (isset($caso->error)) {
                $estado = 'error';
                $mensaje = (string) $caso->error;
            } elseif (isset($caso->skipped)) {
                $estado = 'omitida';
            }

            $clase = (string) ($caso['class'] ?? '');
            $pruebas[] = [
                'grupo' => class_basename(str_replace('.', '\\', $clase)) ?: 'Pruebas',
                'nombre' => str_replace('_', ' ', preg_replace('/^test_?/', '', (string) $caso['name'])),
                'estado' => $estado,
                'tiempo' => round((float) $caso['time'], 3),
                'mensaje' => $mensaje ? mb_substr(trim($mensaje), 0, 1500) : null,
            ];
        }

        $contar = fn ($estado) => count(array_filter($pruebas, fn ($p) => $p['estado'] === $estado));

        return [
            'total' => count($pruebas),
            'aprobadas' => $contar('aprobada'),
            'fallidas' => $contar('fallida'),
            'errores' => $contar('error'),
            'omitidas' => $contar('omitida'),
            'pruebas' => $pruebas,
        ];
    }
}
