<?php

namespace App\Livewire;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Public NIS data page (/pages/data): the introduction events with the
 * admin table's filters, each species linking to its NisSpecies page.
 */
class NisData extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            // Events of species deleted from the catalogue have no page to link to.
            ->query(IntroEventRecord::query()
                ->whereHas('taxon')
                ->with('taxon')
                ->withCount(['occurrences' => fn (Builder $query): Builder => $query->where('status', OccurrenceStatus::APPROVED)]))
            ->columns([
                TextColumn::make('taxon.scientificname')
                    ->label('NIS Scientific Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->html()
                    ->formatStateUsing(fn ($state, IntroEventRecord $record): string => "<span class='italic'>".e((string) $state).'</span>'
                        .($record->taxon?->authority ? ' ('.e((string) $record->taxon->authority).')' : ''))
                    ->description(fn (IntroEventRecord $record): ?string => IntroEventRecordsTable::recordedAs($record))
                    ->url(fn (IntroEventRecord $record): string => route('data.species', $record))
                    ->color('primary'),
                TextColumn::make('first_introduction_year')
                    ->label('1st Year of Introduction')
                    ->wrapHeader()
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('first_country')
                    ->label('1st Country of Introduction')
                    ->wrapHeader()
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => IntroEventRecordsTable::countryName($state))
                    ->placeholder('-')
                    ->visibleFrom('lg'),
                TextColumn::make('nis_status')
                    ->label('NIS Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('establishment_status')
                    ->label('Establishment Status')
                    ->wrapHeader()
                    ->badge()
                    ->sortable()
                    ->visibleFrom('lg'),
                TextColumn::make('occurrences_count')
                    ->label('Occurrences')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters(IntroEventRecordsTable::getFilters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(12)
            ->extraAttributes(['class' => '[&_.fi-ta-filters>.fi-grid]:items-center'])
            ->recordUrl(fn (IntroEventRecord $record): string => route('data.species', $record))
            ->defaultSort('taxon.scientificname');
    }

    public function render(): View
    {
        return view('livewire.nis-data')->extends('app')->section('content');
    }
}
