<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetCodeNotification extends Notification
{
    use Queueable;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de recuperación de contraseña - MANTIA')
            ->greeting('¡Hola!')
            ->line('Has solicitado restablecer tu contraseña en MANTIA.')
            ->line('Tu código de verificación de 6 dígitos es:')
            ->line('**' . $this->code . '**')
            ->line('Este código expirará en 15 minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este mensaje.');
    }
}
