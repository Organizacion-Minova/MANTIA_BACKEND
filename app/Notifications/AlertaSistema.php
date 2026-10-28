<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AlertaSistema extends Notification
{
    use Queueable;

    public function __construct(
        public string $tipo,
        public string $mensaje,
        public string $nivel = 'info',
        public ?string $enlace = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $datos = [
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'nivel' => $this->nivel,
        ];

        // Pantalla a la que lleva la alerta al hacer clic (la campana ya la usa)
        if ($this->enlace) {
            $datos['enlace'] = $this->enlace;
        }

        return $datos;
    }
}