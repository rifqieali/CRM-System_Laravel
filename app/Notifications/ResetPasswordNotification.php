<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Password — '.config('app.name'))
            ->greeting('Halo!')
            ->line('Anda menerima email ini karena ada permintaan reset password untuk akun Anda.')
            ->action('Reset Password', $url)
            ->line('Tautan ini akan kedaluwarsa dalam '.config('auth.passwords.users.expire', 60).' menit.')
            ->line('Jika Anda tidak meminta reset, abaikan email ini.');
    }
}
