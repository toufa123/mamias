<?php

namespace App\Filament\Resources\NisSuggestions\Tables;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Notifications\NisSuggestionApproved;
use App\Notifications\NisSuggestionRejected;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NisSuggestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getScientificNameColumn(),
                self::getAuthorityColumn(),
                self::getAphiaIdColumn(),
                self::getStatusColumn(),
                self::getSubmitterColumn(),
                self::getSubmittedAtColumn(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LiteratureStatus::class),
                Filter::make('date_from')
                    ->form([
                        DatePicker::make('date_from')->label('Submitted from'),
                        DatePicker::make('date_until')->label('Submitted until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['date_until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->recordActions([
                self::getApproveAction(),
                self::getRejectAction(),
                ViewAction::make(),
            ])
            ->recordAction(ViewAction::class)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getScientificNameColumn(): TextColumn
    {
        return TextColumn::make('suggested_scientific_name')
            ->label('Scientific Name')
            ->searchable()
            ->sortable()
            ->html()
            ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>');
    }

    public static function getAuthorityColumn(): TextColumn
    {
        return TextColumn::make('authority')
            ->label('Authority')
            ->placeholder('—')
            ->toggleable();
    }

    public static function getAphiaIdColumn(): TextColumn
    {
        return TextColumn::make('aphia_id')
            ->label('Aphia ID')
            ->placeholder('—')
            ->url(fn (NisSuggestion $record): ?string => $record->aphia_id
                ? "https://www.marinespecies.org/aphia.php?p=taxdetails&id={$record->aphia_id}"
                : null)
            ->openUrlInNewTab()
            ->icon('tabler-external-link')
            ->toggleable();
    }

    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable();
    }

    public static function getSubmitterColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Submitted By')
            ->sortable()
            ->searchable();
    }

    public static function getSubmittedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Submitted')
            ->dateTime()
            ->sortable();
    }

    public static function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('tabler-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve suggestion')
            ->modalDescription('This will create a new Taxon draft in the MAMIAS catalogue and notify the submitter.')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::PENDING)
            ->action(function (NisSuggestion $record): void {
                $alreadyExists = Taxon::where('scientificname', $record->suggested_scientific_name)->exists();

                if (! $alreadyExists) {
                    Taxon::create([
                        'scientificname' => $record->suggested_scientific_name,
                        'aphia_id' => $record->aphia_id,
                        'authority' => $record->authority,
                        'catalogue_status' => Catalogue_Status::not_checked,
                        'notes' => $record->bibliography,
                    ]);
                }

                $record->update(['status' => LiteratureStatus::APPROVED]);

                $record->user?->notify(new NisSuggestionApproved($record));

                $body = $alreadyExists
                    ? "\"{$record->suggested_scientific_name}\" already exists in the catalogue."
                    : "A Taxon draft was created for \"{$record->suggested_scientific_name}\".";

                Notification::make()
                    ->title('Suggestion approved')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }

    public static function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('tabler-x')
            ->color('danger')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::PENDING)
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Rejection reason')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the suggestion is being rejected…'),
            ])
            ->action(function (NisSuggestion $record, array $data): void {
                $record->update([
                    'status' => LiteratureStatus::REJECTED,
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                $record->user?->notify(new NisSuggestionRejected($record));

                Notification::make()
                    ->title('Suggestion rejected')
                    ->warning()
                    ->send();
            });
    }
}
