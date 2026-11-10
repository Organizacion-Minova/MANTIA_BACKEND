<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AlertaSistema;
use Illuminate\Auth\Events\Login;

class RegistrarInicioSesion
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->notify(new AlertaSistema(
                tipo: 'login',
                mensaje: "Inicio de sesión: {$event->user->name}",
                nivel: 'info'
            ));
        }
    }
}