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
            ->subject('Nueva solicitud de cuenta en MANTIA')
            ->greeting('¡Hola, Administrador!')
            ->line('Se ha registrado una nueva solicitud de cuenta en el sistema con los siguientes datos:')
            ->line('• Nombre: ' . $this->newUser->name)
            ->line('• Correo: ' . $this->newUser->email)
            ->action('Gestionar Solicitudes', url('http://localhost:5173/login'))
            ->line('Por favor, ingresa al sistema para aprobar o rechazar esta solicitud.');
    }
}
