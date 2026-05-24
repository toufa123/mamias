<?php

namespace App\Filament\Resources\NisSuggestions\Pages;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\NisSuggestionResource;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Notifications\NisSuggestionApproved;
use App\Notifications\NisSuggestionRejected;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewNisSuggestion extends ViewRecord
{
    protected static string $resource = NisSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('tabler-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve suggestion')
                ->modalDescription('This will create a new Taxon draft in the MAMIAS catalogue and notify the submitter.')
                ->visible(fn (): bool => $this->record->status === LiteratureStatus::PENDING)
                ->action(function (): void {
                    /** @var NisSuggestion $suggestion */
                    $suggestion = $this->record;

                    $alreadyExists = Taxon::where('scientificname', $suggestion->suggested_scientific_name)->exists();

                    if (! $alreadyExists) {
                        Taxon::create([
                            'scientificname' => $suggestion->suggested_scientific_name,
                            'aphia_id' => $suggestion->aphia_id,
                            'authority' => $suggestion->authority,
                            'catalogue_status' => Catalogue_Status::not_checked,
                            'notes' => $suggestion->bibliography,
                        ]);
                    }

                    $suggestion->update(['status' => LiteratureStatus::APPROVED]);

                    $suggestion->user?->notify(new NisSuggestionApproved($suggestion));

                    $body = $alreadyExists
                        ? "\"{$suggestion->suggested_scientific_name}\" already exists in the catalogue."
                        : "A Taxon draft was created for \"{$suggestion->suggested_scientific_name}\".";

                    Notification::make()
                        ->title('Suggestion approved')
                        ->body($body)
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'rejection_reason']);
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('tabler-x')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === LiteratureStatus::PENDING)
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('Explain why the suggestion is being rejected…'),
                ])
                ->action(function (array $data): void {
                    /** @var NisSuggestion $suggestion */
                    $suggestion = $this->record;

                    $suggestion->update([
                        'status' => LiteratureStatus::REJECTED,
                        'rejection_reason' => $data['rejection_reason'],
                    ]);

                    $suggestion->user?->notify(new NisSuggestionRejected($suggestion));

                    Notification::make()
                        ->title('Suggestion rejected')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'rejection_reason']);
                }),
        ];
    }
}
