<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyAccountNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Enlace firmado: nadie puede inventarlo ni cambiarle el id.
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Verifica tu cuenta en MANTIA')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Recibimos una solicitud para crear una cuenta en MANTIA con este correo.')
            ->action('Verificar mi correo', $url)
            ->line('El enlace dura 24 horas. Si no fuiste tú, ignora este mensaje y la cuenta se borrará sola.');
    }
}