<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AlertaSistema;
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
    /** A todos los usuarios aprobados. */
    public static function todos(string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        self::enviar(self::aprobados()->get(), $tipo, $mensaje, $nivel);
    }

    /** Solo a los usuarios aprobados con un rol. Si nadie tiene ese rol, a todos. */
    public static function porRol(string $rol, string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        try {
            $usuarios = self::aprobados()->role($rol)->get();
        } catch (Throwable $e) {
            // El rol no existe todavía (por ejemplo, base de datos sin seeders)
            $usuarios = collect();
        }

        if ($usuarios->isEmpty()) {
            self::todos($tipo, $mensaje, $nivel);
            return;
        }

        self::enviar($usuarios, $tipo, $mensaje, $nivel);
    }

    public static function usuario(User $usuario, string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        self::enviar(collect([$usuario]), $tipo, $mensaje, $nivel);
    }

    private static function aprobados()
    {
        return User::query()->where('status', 'approved');
    }

    private static function enviar($usuarios, string $tipo, string $mensaje, string $nivel): void
    {
        foreach ($usuarios as $usuario) {
            try {
                $usuario->notify(new AlertaSistema($tipo, $mensaje, $nivel));
            } catch (Throwable $e) {
                Log::error('No se pudo enviar una alerta', [
                    'usuario' => $usuario->id,
                    'tipo' => $tipo,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}