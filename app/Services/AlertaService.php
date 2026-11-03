<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AlertaSistema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envía las alertas del sistema (campana de notificaciones).
 *
 * Si enviar una alerta falla, se registra en el log y se sigue: una alerta
 * nunca debe impedir que se guarde una máquina, una medición, etc.
 */
class AlertaService
{
    /** A todos los usuarios aprobados. $enlace = pantalla a la que lleva la alerta al hacer clic. */
    public static function todos(string $tipo, string $mensaje, string $nivel = 'info', ?string $enlace = null): void
    {
        self::enviar(self::aprobados()->get(), $tipo, $mensaje, $nivel, $enlace);
    }

    /** Solo a los usuarios aprobados con un rol (o con alguno de varios roles). Si nadie lo tiene, a todos. */
    public static function porRol(string|array $rol, string $tipo, string $mensaje, string $nivel = 'info', ?string $enlace = null): void
    {
        try {
            $usuarios = self::aprobados()->role($rol)->get();
        } catch (Throwable $e) {
            // El rol no existe todavía (por ejemplo, base de datos sin seeders)
            $usuarios = collect();
        }

        if ($usuarios->isEmpty()) {
            self::todos($tipo, $mensaje, $nivel, $enlace);
            return;
        }

        self::enviar($usuarios, $tipo, $mensaje, $nivel, $enlace);
    }

    public static function usuario(User $usuario, string $tipo, string $mensaje, string $nivel = 'info', ?string $enlace = null): void
    {
        self::enviar(collect([$usuario]), $tipo, $mensaje, $nivel, $enlace);
    }

    private static function aprobados()
    {
        return User::query()->where('status', 'approved');
    }

    private static function enviar($usuarios, string $tipo, string $mensaje, string $nivel, ?string $enlace = null): void
    {
        foreach ($usuarios as $usuario) {
            if (self::repetida($usuario, $tipo, $mensaje, $nivel, $enlace)) {
                continue;
            }

            try {
                $usuario->notify(new AlertaSistema($tipo, $mensaje, $nivel, $enlace));
            } catch (Throwable $e) {
                Log::error('No se pudo enviar una alerta', [
                    'usuario' => $usuario->id,
                    'tipo' => $tipo,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Si el usuario ya recibió esta misma alerta hace menos de 10 segundos, no se repite.
     * Pasa cuando se pulsa "Guardar" varias veces seguidas y llegan varias peticiones iguales.
     */
    private static function repetida($usuario, string $tipo, string $mensaje, string $nivel, ?string $enlace): bool
    {
        $clave = "alerta:{$usuario->id}:" . md5("{$tipo}|{$mensaje}|{$nivel}|{$enlace}");

        try {
            // add() solo guarda si la clave no existe: la primera alerta pasa, las copias no
            return ! Cache::add($clave, true, now()->addSeconds(10));
        } catch (Throwable $e) {
            return false; // si la caché falla, mejor enviar la alerta que perderla
        }
    }
}