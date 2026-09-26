<?php

namespace App\Notifications;

use App\Enums\LiteratureStatus;
use App\Models\Literature;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the submitter a moderator approved or rejected their reference,
 * with the moderator's comment. Links to "My references", which every
 * submitter can open, rather than the panel, which public users cannot.
 */
class LiteratureReviewed extends Notification
{
    use Queueable;

    public function __construct(public Literature $literature) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reference = $this->literature->short_ref ?: $this->literature->code;

        return (new MailMessage)
            ->subject($this->isApproved()
                ? '[MAMIAS] Your reference has been approved'
                : '[MAMIAS] Your reference was not accepted')
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line($this->isApproved()
                ? "Your reference \"{$reference}\" ({$this->literature->code}) has been approved and can now be linked to species records."
                : "Your reference \"{$reference}\" ({$this->literature->code}) has not been accepted at this time.")
            ->when($this->literature->review_comment, fn (MailMessage $mail): MailMessage => $mail
                ->line('**Reviewer comment:** '.$this->literature->review_comment))
            ->action('View my references', route('references'))
            ->line('Thank you for your contribution to the MAMIAS database.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->isApproved() ? 'Reference approved' : 'Reference rejected')
            ->icon($this->isApproved() ? 'tabler-circle-check' : 'tabler-circle-x')
            ->iconColor($this->isApproved() ? 'success' : 'danger')
            ->body(trim("{$this->literature->code}: {$this->literature->review_comment}", ': '))
            ->actions([
                FilamentAction::make('view')
                    ->button()
                    ->url(route('references')),
            ])
            ->getDatabaseMessage();
    }

    private function isApproved(): bool
    {
        return $this->literature->status === LiteratureStatus::APPROVED;
    }
}
