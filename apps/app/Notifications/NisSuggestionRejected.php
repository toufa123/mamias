<?php

namespace App\Notifications;

use App\Models\NisSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NisSuggestionRejected extends Notification
{
    use Queueable;

    public function __construct(public NisSuggestion $suggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your species suggestion was not accepted')
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line("Unfortunately, your suggestion for *{$this->suggestion->suggested_scientific_name}* has not been accepted at this time.")
            ->when($this->suggestion->rejection_reason, function (MailMessage $mail): MailMessage {
                return $mail->line('**Reason:** '.$this->suggestion->rejection_reason);
            })
            ->line('You are welcome to submit a revised suggestion with additional supporting information.')
            ->line('Thank you for your contribution to the MAMIAS database.');
    }
}
