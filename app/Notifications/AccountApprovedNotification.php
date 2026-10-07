<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApprovedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Tu cuenta en MANTIA ha sido aprobada!')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Nos complace informarte que tu solicitud de cuenta en MANTIA ha sido aprobada por el administrador.')
            ->line('Ya puedes iniciar sesión en el sistema y comenzar a utilizar la plataforma.')
            ->action('Iniciar Sesión', url('http://localhost:5173/login'))
            ->line('¡Gracias por unirte a MANTIA!');
    }
}
