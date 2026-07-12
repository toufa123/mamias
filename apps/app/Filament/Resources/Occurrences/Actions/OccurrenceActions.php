<?php

declare(strict_types=1);

namespace App\Filament\Resources\Occurrences\Actions;

use App\Enums\OccurrenceStatus;
use App\Models\Occurrence;
use App\Notifications\OccurrenceApproved;
use App\Notifications\OccurrenceRejected;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/**
 * Provides approve and reject actions for occurrence records.
 * Approve/reject update the occurrence status and notify the
 * submitter via database notification.
 */
class OccurrenceActions
{
    /**
     * @param  Closure|null  $afterAction  Optional callback invoked after the approve routine.
     * @return Action The configured approve action.
     */
    public static function makeApproveAction(?Closure $afterAction = null): Action
    {
        return Action::make('approve')
            ->label(fn (Occurrence $record): string => $record->status === OccurrenceStatus::APPROVED ? 'Re-approve' : 'Approve')
            ->icon('tabler-check')
            ->color('success')
            ->visible(fn (Occurrence $record): bool => $record->status !== OccurrenceStatus::APPROVED)
            ->modalHeading(fn (Occurrence $record): string => $record->status === OccurrenceStatus::APPROVED ? 'Re-approve occurrence' : 'Approve occurrence')
            ->modalSubmitActionLabel('Approve Occurrence')
            ->schema([
                Textarea::make('moderation_notes')
                    ->label('Moderation notes (optional)')
                    ->rows(3),
            ])
            ->action(function (Occurrence $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => OccurrenceStatus::APPROVED,
                    'moderation_notes' => $data['moderation_notes'] ?? null,
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'old_status' => OccurrenceStatus::PENDING->value,
                        'new_status' => OccurrenceStatus::APPROVED->value,
                        'moderation_notes' => $data['moderation_notes'] ?? null,
                    ])
                    ->event('approved')
                    ->log('approved');

                $record->user?->notify(new OccurrenceApproved($record));

                Notification::make()
                    ->title('Occurrence approved')
                    ->success()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }

    /**
     * @param  Closure|null  $afterAction  Optional callback invoked after the reject routine.
     * @return Action The configured reject action.
     */
    public static function makeRejectAction(?Closure $afterAction = null): Action
    {
        return Action::make('reject')
            ->label(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'Re-reject' : 'Reject')
            ->icon('tabler-x')
            ->color('danger')
            ->visible(fn (Occurrence $record): bool => $record->status !== OccurrenceStatus::REJECTED)
            ->schema([
                Textarea::make('moderation_notes')
                    ->label('Rejection reason')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the occurrence is being rejected…'),
            ])
            ->action(function (Occurrence $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => OccurrenceStatus::REJECTED,
                    'moderation_notes' => $data['moderation_notes'],
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'old_status' => OccurrenceStatus::PENDING->value,
                        'new_status' => OccurrenceStatus::REJECTED->value,
                        'moderation_notes' => $data['moderation_notes'],
                    ])
                    ->event('rejected')
                    ->log('rejected');

                $record->user?->notify(new OccurrenceRejected($record));

                Notification::make()
                    ->title('Occurrence rejected')
                    ->warning()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }
}
