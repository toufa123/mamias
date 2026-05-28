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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                ->modalHeading('Approve suggestion')
                ->modalSubmitActionLabel('Create Taxon & Approve')
                ->visible(fn (): bool => $this->record->status === LiteratureStatus::PENDING)
                ->schema([
                    TextInput::make('scientificname')
                        ->label('Scientific Name')
                        ->default(fn (): string => $this->record->suggested_scientific_name)
                        ->required(),
                    TextInput::make('authority')
                        ->label('Authority')
                        ->default(fn (): ?string => $this->record->authority),
                    Select::make('catalogue_status')
                        ->label('Catalogue Status')
                        ->options(Catalogue_Status::class)
                        ->default(Catalogue_Status::not_checked)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var NisSuggestion $suggestion */
                    $suggestion = $this->record;

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

                    $suggestion->update([
                        'status' => LiteratureStatus::APPROVED,
                        'taxon_id' => $taxonId,
                    ]);

                    $suggestion->user?->notify(new NisSuggestionApproved($suggestion));

                    $body = $alreadyExists
                        ? "\"{$data['scientificname']}\" already exists in the catalogue."
                        : "Taxon \"{$data['scientificname']}\" was created in the catalogue.";

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
