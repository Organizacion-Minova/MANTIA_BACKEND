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
        // Pantalla del frontend a la que lleva la alerta (ej: /machines/cardMachine/5)
        public ?string $enlace = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_filter([
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'nivel' => $this->nivel,
            'enlace' => $this->enlace,
        ], fn ($valor) => $valor !== null);
    }
}