<?php

namespace App\Notifications;

use App\Enums\OccurrenceStatus;
use App\Models\Occurrence;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells moderators that a user report is waiting in the review queue,
 * either freshly submitted or revised after a rejection.
 */
class OccurrenceSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Occurrence $occurrence, public bool $isResubmission = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MAMIAS] '.$this->title().': '.$this->speciesName())
            ->line($this->summary())
            ->when($this->isResubmission && $this->occurrence->moderation_notes, fn (MailMessage $mail): MailMessage => $mail
                ->line('**Previous rejection reason:** '.$this->occurrence->moderation_notes))
            ->action('Review occurrences', $this->reviewUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->icon('tabler-binoculars')
            ->iconColor('warning')
            ->body($this->summary())
            ->actions([
                FilamentAction::make('review')
                    ->button()
                    ->url($this->reviewUrl()),
            ])
            ->getDatabaseMessage();
    }

    private function title(): string
    {
        return $this->isResubmission ? 'Occurrence resubmitted' : 'New occurrence report';
    }

    private function summary(): string
    {
        $reporter = $this->occurrence->user->name ?? 'A user';
        $verb = $this->isResubmission ? 'revised and resubmitted' : 'reported';

        return "{$reporter} {$verb} an occurrence of {$this->speciesName()}.";
    }

    private function speciesName(): string
    {
        return $this->occurrence->taxon->scientificname ?? 'an unnamed species';
    }

    private function reviewUrl(): string
    {
        return route('filament.mamias.resources.occurrences.index', [
            'filters' => ['status' => ['value' => OccurrenceStatus::PENDING->value]],
        ]);
    }
}
