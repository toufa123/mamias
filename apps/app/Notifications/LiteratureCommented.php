<?php

namespace App\Notifications;

use App\Models\Literature;
use App\Models\User;
use Filament\Actions\Action as FilamentAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Kirschbaum\Commentions\Comment;

/**
 * A new message in a reference's discussion. Moderators are sent to the
 * panel; submitters to "My references", the only place they can reply.
 */
class LiteratureCommented extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[MAMIAS] New comment on reference {$this->literature()->code}")
            ->greeting('Hello, '.$notifiable->name.'.')
            ->line("{$this->authorName()} commented on \"{$this->literature()->short_ref}\" ({$this->literature()->code}):")
            ->line('> '.$this->excerpt())
            ->action('Reply', $this->url($notifiable));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("New comment on {$this->literature()->code}")
            ->icon('tabler-message-circle')
            ->iconColor('info')
            ->body("{$this->authorName()}: {$this->excerpt()}")
            ->actions([
                FilamentAction::make('view')
                    ->button()
                    ->url($this->url($notifiable)),
            ])
            ->getDatabaseMessage();
    }

    private function literature(): Literature
    {
        return $this->comment->commentable;
    }

    private function authorName(): string
    {
        return $this->comment->author?->name ?? 'Someone';
    }

    private function excerpt(): string
    {
        return Str::limit(trim(strip_tags($this->comment->body)), 200);
    }

    private function url(object $notifiable): string
    {
        return $notifiable instanceof User && $notifiable->canAccessPanel(Filament::getPanel('mamias'))
            ? route('filament.mamias.resources.literatures.edit', ['record' => $this->literature()])
            : route('references');
    }
}
