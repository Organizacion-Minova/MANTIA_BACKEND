<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AlertaSistema;
use Illuminate\Auth\Events\Logout;

class RegistrarCierreSesion
{
    public function handle(Logout $event): void
    {
        // El evento trae un "Authenticatable" genérico; comprobar que es nuestro User
        // le da acceso a notify() (trait Notifiable) y evita el aviso rojo del editor.
        if ($event->user instanceof User) {
            $event->user->notify(new AlertaSistema(
                tipo: 'logout',
                mensaje: "Cierre de sesión: {$event->user->name}",
                nivel: 'info'
            ));
        }
    }
}