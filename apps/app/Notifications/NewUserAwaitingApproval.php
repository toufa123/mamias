<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Asks super_admins to verify a newly registered account while registration
 * approval replaces the email verification link (auth.registration_approval).
 *
 * Queued, one job per channel: a mail outage cannot fail the registration or
 * hold back the panel notification.
 */
class NewUserAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] New account awaiting approval: '.$this->user->name)
            ->line($this->summary())
            ->action('Review new accounts', $this->reviewUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New account awaiting approval')
            ->icon('tabler-user-plus')
            ->iconColor('warning')
            ->body($this->summary())
            ->actions([
                FilamentAction::make('review')
                    ->button()
                    ->url($this->reviewUrl()),
            ])
            ->getDatabaseMessage();
    }

    private function summary(): string
    {
        return "{$this->user->name} ({$this->user->email}) registered and is waiting for you to verify the account.";
    }

    private function reviewUrl(): string
    {
        return route('filament.mamias.resources.users.index', [
            'filters' => ['email_verified_at' => ['value' => '0']],
        ]);
    }
}
