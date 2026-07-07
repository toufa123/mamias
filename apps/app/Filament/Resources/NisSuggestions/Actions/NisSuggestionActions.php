<?php

declare(strict_types=1);

namespace App\Filament\Resources\NisSuggestions\Actions;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Notifications\NisSuggestionApproved;
use App\Notifications\NisSuggestionRejected;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class NisSuggestionActions
{
    public static function makeApproveAction(?Closure $afterAction = null): Action
    {
        return Action::make('approve')
            ->label(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-approve' : 'Approve')
            ->icon('tabler-check')
            ->color('success')
            ->visible(fn (NisSuggestion $record): bool => $record->status !== LiteratureStatus::APPROVED)
            ->modalHeading(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-approve suggestion' : 'Approve suggestion')
            ->modalSubmitActionLabel('Create Taxon & Approve')
            ->schema([
                TextInput::make('scientificname')
                    ->label('Scientific Name')
                    ->default(fn (NisSuggestion $record): string => $record->suggested_scientific_name)
                    ->required(),
                TextInput::make('authority')
                    ->label('Authority')
                    ->default(fn (NisSuggestion $record): ?string => $record->authority),
                Select::make('catalogue_status')
                    ->label('Catalogue Status')
                    ->options(Catalogue_Status::class)
                    ->default(Catalogue_Status::not_checked)
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->action(function (NisSuggestion $record, array $data) use ($afterAction): void {
                $alreadyExists = Taxon::where('scientificname', $data['scientificname'])->exists();

                $taxonId = null;

                if (! $alreadyExists) {
                    $taxon = Taxon::create([
                        'scientificname' => $data['scientificname'],
                        'authority' => $data['authority'],
                        'catalogue_status' => $data['catalogue_status'],
                        'notes' => $data['notes'],
                    ]);
                    $taxonId = $taxon->id;
                }

                $record->update([
                    'status' => LiteratureStatus::APPROVED,
                    'taxon_id' => $taxonId,
                    'rejection_reason' => null,
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'taxon_created' => ! $alreadyExists,
                        'scientificname' => $data['scientificname'],
                    ])
                    ->event('approved')
                    ->log('approved');

                $record->user?->notify(new NisSuggestionApproved($record));

                $body = $alreadyExists
                    ? "\"{$data['scientificname']}\" already exists in the catalogue."
                    : "Taxon \"{$data['scientificname']}\" was created in the catalogue.";

                Notification::make()
                    ->title('Suggestion approved')
                    ->body($body)
                    ->success()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }

    public static function makeRejectAction(?Closure $afterAction = null): Action
    {
        return Action::make('reject')
            ->label(fn (NisSuggestion $record): string => $record->status === LiteratureStatus::REJECTED ? 'Re-reject' : 'Reject')
            ->icon('tabler-x')
            ->color('danger')
            ->visible(fn (NisSuggestion $record): bool => $record->status !== LiteratureStatus::REJECTED)
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Rejection reason')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the suggestion is being rejected…'),
            ])
            ->action(function (NisSuggestion $record, array $data) use ($afterAction): void {
                $record->update([
                    'status' => LiteratureStatus::REJECTED,
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'rejection_reason' => $data['rejection_reason'],
                    ])
                    ->event('rejected')
                    ->log('rejected');

                $record->user?->notify(new NisSuggestionRejected($record));

                Notification::make()
                    ->title('Suggestion rejected')
                    ->warning()
                    ->send();

                $afterAction?->call($record, $data);
            });
    }
}
