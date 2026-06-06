<?php

namespace App\Notifications;

use App\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OccurrenceRejected extends Notification
{
    use Queueable;

    public function __construct(public Occurrence $occurrence) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] Your occurrence report was not accepted')
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line("Unfortunately, your occurrence report for *{$this->occurrence->taxon?->scientificname}* has not been accepted at this time.")
            ->when($this->occurrence->moderation_notes, function (MailMessage $mail): MailMessage {
                return $mail->line('**Reason:** '.$this->occurrence->moderation_notes);
            })
            ->line('Thank you for your contribution to the MAMIAS database.');
    }
}
