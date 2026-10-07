<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Notifications\AlertaSistema;

class RegistrarInicioSesion
{
    public function handle(Login $event): void
    {
        $event->user->notify(new AlertaSistema(
            tipo: 'login',
            mensaje: "Inicio de sesión: {$event->user->name}",
            nivel: 'info'
        ));
    }
}