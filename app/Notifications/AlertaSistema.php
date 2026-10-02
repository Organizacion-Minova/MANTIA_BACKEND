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
        public string $nivel = 'info'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'nivel' => $this->nivel,
        ];
    }
}