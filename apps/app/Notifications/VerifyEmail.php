<?php

namespace App\Notifications;

use Filament\Auth\Notifications\VerifyEmail as FilamentVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends FilamentVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->greeting('Welcome to MAMIAS!')
            ->line('Please click the button below to verify your email address and activate your account.')
            ->action('Visit MAMIAS', $url)
            ->line('If you did not create an account, no further action is required.');
    }
}
