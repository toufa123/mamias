<?php

namespace App\Filament\Resources\NisSuggestions\Tables;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Notifications\NisSuggestionApproved;
use App\Notifications\NisSuggestionRejected;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NisSuggestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['user', 'taxon'])
            )
            ->columns([
                self::getScientificNameColumn(),
                self::getAuthorityColumn(),
                self::getAphiaIdColumn(),
                self::getStatusColumn(),
                self::getMapColumn(),
                self::getTaxonColumn(),
                self::getSubmitterColumn(),
                self::getSubmittedAtColumn(),
            ])
            ->filters([
                TrashedFilter::make(),
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
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->recordAction(ViewAction::class)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
            ->sortable()
            ->tooltip(fn (NisSuggestion $record): ?string => match (true) {
                $record->status === LiteratureStatus::REJECTED && $record->rejection_reason => $record->rejection_reason,
                default => null,
            });
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

    public static function getTaxonColumn(): TextColumn
    {
        return TextColumn::make('taxon')
            ->label('Taxon')
            ->placeholder('—')
            ->state(fn (NisSuggestion $record): ?string => $record->taxon?->scientificname)
            ->url(fn (NisSuggestion $record): ?string => $record->taxon_id
                ? route('filament.mamias.resources.taxons.taxa.edit', ['record' => $record->taxon_id])
                : null)
            ->openUrlInNewTab()
            ->icon('tabler-fish')
            ->toggleable();
    }

    public static function getMapColumn(): MapColumn
    {
        return MapColumn::make('location')
            ->label('Location')
            ->state(fn (?NisSuggestion $record): ?array => $record?->location?->toArray())
            ->height(72)
            ->width(108)
            ->zoom(5)
            ->static()
            ->pickMarker(fn (Marker $marker) => $marker->red())
            ->placeholder('—')
            ->hidden(fn (?NisSuggestion $record): bool => $record?->getRawOriginal('location') === null)
            ->toggleable();
    }

    public static function getApproveAction(): Action
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
            ->action(function (NisSuggestion $record, array $data): void {
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

                $record->user?->notify(new NisSuggestionApproved($record));

                $body = $alreadyExists
                    ? "\"{$data['scientificname']}\" already exists in the catalogue."
                    : "Taxon \"{$data['scientificname']}\" was created in the catalogue.";

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
