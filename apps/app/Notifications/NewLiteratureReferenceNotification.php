<?php

namespace App\Notifications;

use App\Models\Literature;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLiteratureReferenceNotification extends Notification
{
    use Queueable;

    public function __construct(public Literature $literature) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $creatorName = $this->literature->creator?->name ?? 'A user';
        $title = $this->literature->short_ref ?: $this->literature->code;

        return (new MailMessage)
            ->subject('[MAMIAS] New Reference Submitted: '.$this->literature->code)
            ->line("{$creatorName} has submitted a new bibliographic reference.")
            ->line("Reference: {$title}")
            ->action('Review Reference', route('filament.mamias.resources.literatures.edit', ['record' => $this->literature]))
            ->line('Thank you for keeping the MAMIAS database up to date.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New Reference Submitted')
            ->icon('tabler-file-plus')
            ->iconColor('success')
            ->body("{$this->literature->code} submitted by ".($this->literature->creator?->name ?? 'a user'))
            ->actions([
                FilamentAction::make('view')
                    ->button()
                    ->url(route('filament.mamias.resources.literatures.edit', ['record' => $this->literature])),
            ])
            ->getDatabaseMessage();
    }
}
