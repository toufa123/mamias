<?php

namespace App\Notifications;

use App\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OccurrenceApproved extends Notification
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
            ->subject('[MAMIAS] Your occurrence report has been approved')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line("Your occurrence report for *{$this->occurrence->taxon?->scientificname}* has been approved and is now visible in the MAMIAS database.")
            ->line('Thank you for contributing to the MAMIAS Non-Indigenous Species database.');
    }
}
