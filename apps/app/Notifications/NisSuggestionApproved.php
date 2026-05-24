<?php

namespace App\Notifications;

use App\Models\NisSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NisSuggestionApproved extends Notification
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
            ->subject('[MAMIAS] Your species suggestion has been approved')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line("Your suggestion for *{$this->suggestion->suggested_scientific_name}* has been approved and added to the MAMIAS catalogue as a new taxon draft.")
            ->line('Our team will enrich the record with additional data from WoRMS and other sources.')
            ->line('Thank you for contributing to the MAMIAS Non-Indigenous Species database.');
    }
}
