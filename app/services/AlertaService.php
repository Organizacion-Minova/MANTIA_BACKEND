<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AlertaSistema;

class AlertaService
{
    public static function todos(string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        User::all()->each(
            fn ($user) => $user->notify(new AlertaSistema($tipo, $mensaje, $nivel))
        );
    }

    public static function porRol(string $rol, string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        $usuarios = User::role($rol)->get();

        if ($usuarios->isEmpty()) {
            self::todos($tipo, $mensaje, $nivel);
            return;
        }

        $usuarios->each(
            fn ($user) => $user->notify(new AlertaSistema($tipo, $mensaje, $nivel))
        );
    }

    public static function usuario(User $usuario, string $tipo, string $mensaje, string $nivel = 'info'): void
    {
        $usuario->notify(new AlertaSistema($tipo, $mensaje, $nivel));
    }
}