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
            ->line('Una persona nueva verificó su correo y quedó como Visitante. Datos:')
            ->line('• Nombre: ' . $this->newUser->name)
            ->line('• Correo: ' . $this->newUser->email)
            ->action('Ir a MANTIA', url('http://localhost:5173/login'))
            ->line('Si es una persona real, asígnale su rol desde el panel de administración. Si no, puedes desactivarla.');
    }
}