<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAccountRequestNotification extends Notification
{
    use Queueable;

    protected $newUser;

    public function __construct($newUser)
    {
        $this->newUser = $newUser;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva cuenta verificada en MANTIA')
            ->greeting('¡Hola, Jefe de mina!')
            ->line('Se ha registrado un nuevo usuario en la plataforma con el rol de visitante pendiente de verificación.')
            ->line('Detalles del usuario:')
            ->line('• Nombre: ' . $this->newUser->name)
            ->line('• Correo: ' . $this->newUser->email)
            ->action('Ir en MANTIA', url('http://localhost:5173/login'))
            ->line('Si reconoces a esta persona, asígnale su rol correspondiente desde el panel de administración.')
            ->salutation(' ');
    }
}