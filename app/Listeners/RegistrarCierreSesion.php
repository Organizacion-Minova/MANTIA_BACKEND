<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Notifications\AlertaSistema;

class RegistrarCierreSesion
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            $event->user->notify(new AlertaSistema(
                tipo: 'logout',
                mensaje: "Cierre de sesión: {$event->user->name}",
                nivel: 'info'
            ));
        }
    }
} 