<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HU-03 (CA-03.3): correo con el enlace de recuperación.
 */
class RestablecerContrasena extends Notification
{
    public function __construct(public readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = config('auth.passwords.users.expire');

        // No se incluye el nombre del usuario: es texto libre y podría usarse para inyectar enlaces en el correo.
        return (new MailMessage())
            ->subject('Recupera el acceso a tu cuenta')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $this->url($notifiable))
            ->line("Este enlace vence en {$minutos} minutos y solo se puede usar una vez.")
            ->line('Si no solicitaste el cambio, ignora este correo: tu contraseña actual seguirá funcionando.')
            ->salutation('Equipo '.config('app.name'));
    }

    /**
     * El enlace se arma con APP_URL y no con el Host de la petición, para que nadie pueda
     * hacer que el correo apunte a otro dominio (envenenamiento del enlace de recuperación).
     */
    public function url(object $notifiable): string
    {
        return rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
    }
}
