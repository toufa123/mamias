<?php

namespace App\Livewire;

use App\Enums\CbdPathwaySubcategory;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Models\IntroEventRecord;
use Filament\Actions\Action;
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
use Livewire\Attributes\Url;
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

    /** In the address, so the dashboards' word clouds can link to a search (?search=…). */
    #[Url(as: 'search')]
    public $tableSearch = '';

    /**
     * ?pathway=3.1 opens the table filtered on that CBD pathway subcategory
     * (the dashboards' word clouds link here). Read once, rather than binding
     * every filter to the address, which would also write the year slider's
     * state into it and break the slider.
     */
    public function mount(): void
    {
        $pathway = CbdPathwaySubcategory::tryFrom((string) request()->query('pathway'));

        if ($pathway) {
            $this->tableFilters = ['pathway_subcategory' => ['values' => [$pathway->value]]];
        }
    }

    /** How to search, filter and read the data, in a scrollable popup (resources/docs/data.md). */
    public function guideAction(): Action
    {
        return LiteratureGuide::action('data', 'How the data explorer works');
    }

    public function table(Table $table): Table
    {
        return $table
            // Events of species deleted from the catalogue have no page to link to.
            ->query(IntroEventRecord::query()
                ->whereHas('taxon')
                ->with('taxon'))
            ->columns(self::columns())
            ->recordActions(self::recordActions())
            ->filters(IntroEventRecordsTable::getFilters())
            // Drawn by the view in their own collapsible block (DESIGN-SYSTEM.md, "Blocks").
            ->filtersLayout(FiltersLayout::Hidden)
            ->filtersFormColumns(12)
            ->recordUrl(fn (IntroEventRecord $record): string => route('data.species', $record))
            ->defaultSort('taxon.scientificname');
    }

    /**
     * Shared with the map page (App\Livewire\NisMap), which lists the same species.
     *
     * @return list<TextColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('taxon.scientificname')
                ->label('NIS Scientific Name')
                // A name, or a family: the family word cloud links to ?search=<family>.
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                    'taxon',
                    fn (Builder $taxon): Builder => $taxon->where(fn (Builder $match): Builder => $match
                        ->where('scientificname', 'ilike', "%{$search}%")
                        ->orWhere('family', 'ilike', "%{$search}%")),
                ))
                ->sortable()
                ->wrap()
                ->html()
                ->formatStateUsing(fn ($state, IntroEventRecord $record): string => "<span class='italic'>".e((string) $state).'</span>'
                    .($record->taxon?->authority ? ' '.e((string) $record->taxon->authority) : ''))
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
        ];
    }

    /**
     * @return list<Action>
     */
    public static function recordActions(): array
    {
        return [
            Action::make('view')
                ->label('View data')
                ->icon('tabler-eye')
                ->url(fn (IntroEventRecord $record): string => route('data.species', $record)),
        ];
    }

    public function render(): View
    {
        return view('livewire.nis-data')->extends('app')->section('content');
    }
}
