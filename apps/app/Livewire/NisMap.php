<?php

namespace App\Livewire;

use App\Enums\EstablishmentStatus;
use App\Enums\OccurrenceStatus;
use App\Enums\Subregion;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Services\MediterraneanDashboard;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public NIS map (/pages/map): the EcAp subregions or the countries of first
 * record on a map (App\Livewire\NisAreaMap), a summary of the picked area, and
 * its species in the data page's table. The data page's filters, rendered
 * above the map, narrow all three; the EcAp subregion filter also limits which
 * subregions the map counts and lets the visitor pick. Approved occurrences
 * can be pinned on top.
 *
 * @property-read array<string, int> $countryCounts
 */
class NisMap extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public const LAYERS = ['subregions', 'countries'];

    /** Which areas the map shows, and so what the summary and table are about. */
    #[Url]
    public string $layer = 'subregions';

    #[Url]
    public string $subregion = 'EMED';

    #[Url]
    public ?string $country = null;

    /** Pin the approved occurrences of the filtered species. */
    #[Url]
    public bool $pins = false;

    public function mount(): void
    {
        if (! in_array($this->layer, self::LAYERS, true)) {
            $this->layer = 'subregions';
        }

        if (! Subregion::tryFrom($this->subregion)) {
            $this->subregion = Subregion::EMED->value;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->inSelectedArea(self::eventsQuery()->with('taxon')))
            ->columns(NisData::columns())
            ->recordActions(NisData::recordActions())
            ->filters(IntroEventRecordsTable::getFilters())
            // Drawn above the map by the view, so they visibly drive it too.
            ->filtersLayout(FiltersLayout::Hidden)
            // Applied as soon as they change: the map and the summary redraw with the table.
            ->deferFilters(false)
            ->filtersFormColumns(12)
            ->recordUrl(fn (IntroEventRecord $record): string => route('data.species', $record))
            ->defaultSort('taxon.scientificname');
    }

    /** How to read and use the map, in a scrollable popup (resources/docs/map.md). */
    public function guideAction(): Action
    {
        return LiteratureGuide::action('map', 'How the map works');
    }

    public function showLayer(string $layer): void
    {
        if (in_array($layer, self::LAYERS, true)) {
            $this->layer = $layer;
            $this->resetPage();
        }
    }

    #[On('subregion-picked')]
    public function pickSubregion(string $code): void
    {
        if (Subregion::tryFrom($code) && $this->allowsSubregion($code)) {
            $this->layer = 'subregions';
            $this->subregion = $code;
            $this->resetPage();
        }
    }

    #[On('country-picked')]
    public function pickCountry(string $name): void
    {
        if (($this->countryCounts[$name] ?? 0) > 0) {
            $this->layer = 'countries';
            $this->country = $name;
            $this->resetPage();
        }
    }

    public function isCountryLayer(): bool
    {
        return $this->layer === 'countries';
    }

    public function selectedSubregion(): Subregion
    {
        return Subregion::from($this->subregion);
    }

    /** "the Aegean-Levantine Sea" or "Israel", for headings. */
    public function selectedAreaLabel(): string
    {
        return $this->isCountryLayer()
            ? IntroEventRecordsTable::countryName($this->country)
            : 'the '.$this->selectedSubregion()->getLabel();
    }

    /** The subregion code or country name the map highlights. */
    public function selectedKey(): string
    {
        return $this->isCountryLayer() ? (string) $this->country : $this->subregion;
    }

    public function selectedCount(): int
    {
        return $this->isCountryLayer()
            ? $this->countryCounts[$this->country] ?? 0
            : $this->counts[$this->subregion] ?? 0;
    }

    /**
     * Species per subregion code among the events the filters keep.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return SubregionRecord::query()
            ->whereIn('intro_event_id', $this->filteredEventIds())
            ->when($this->filteredSubregions(), fn (Builder $query, array $codes): Builder => $query->whereIn('subregion', $codes))
            ->toBase()
            ->selectRaw('subregion, count(distinct intro_event_id) as aggregate')
            ->groupBy('subregion')
            ->pluck('aggregate', 'subregion')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * Species per country of first record among the events the filters keep.
     * first_country is a JSON array, so an event first recorded in two
     * countries counts in both. Countries are the Barcelona Convention's, as
     * on the dashboards: a stored "Gaza strip" counts under Israel
     * (MediterraneanDashboard::REPORTED_AS); the records themselves keep it.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function countryCounts(): array
    {
        // ponytail: unpacked in PHP — about a thousand short arrays; a jsonb_array_elements query if it grows.
        return IntroEventRecord::query()
            ->whereIn('id', $this->filteredEventIds())
            ->pluck('first_country')
            ->flatMap(fn (mixed $countries): array => array_unique(array_map(
                MediterraneanDashboard::reportedCountry(...),
                array_filter((array) $countries),
            )))
            ->countBy()
            ->sortDesc()
            ->all();
    }

    /**
     * Establishment status in the picked area, most common first: the
     * per-subregion status for a subregion, the event's own for a country.
     *
     * @return list<array{label: string, count: int, color: string}>
     */
    #[Computed]
    public function establishment(): array
    {
        $colors = [EstablishmentStatus::Established->value => '#134f4c', EstablishmentStatus::Casual->value => '#7fc0b7', EstablishmentStatus::Invasive->value => '#b5452d'];

        $statuses = $this->isCountryLayer()
            ? $this->selectedEvents()->toBase()
            : $this->selectedRecords()->toBase();

        return $statuses
            ->selectRaw('establishment_status, count(*) as aggregate')
            ->groupBy('establishment_status')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn (object $row): array => [
                'label' => EstablishmentStatus::tryFrom((string) $row->establishment_status)?->getLabel() ?? 'Not assessed',
                'count' => (int) $row->aggregate,
                'color' => $colors[$row->establishment_status] ?? '#b8c4c9',
            ])
            ->all();
    }

    /**
     * The five best-represented phyla in the picked area.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function phyla(): array
    {
        $taxa = (new Taxon)->getTable();

        return IntroEventRecord::query()
            ->whereIn('intro_event_records.id', $this->selectedEvents()->select('intro_event_records.id'))
            ->join($taxa, "{$taxa}.id", '=', 'intro_event_records.taxon_id')
            ->toBase()
            ->selectRaw("coalesce({$taxa}.phylum, 'Unknown') as phylum, count(*) as aggregate")
            ->groupBy('phylum')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->pluck('aggregate', 'phylum')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * The five most recent arrivals in the picked area: the first arrival in
     * the subregion, or the first introduction for a country of first record.
     *
     * @return Collection<int, array{event: IntroEventRecord, year: int}>
     */
    #[Computed]
    public function latestArrivals(): Collection
    {
        if ($this->isCountryLayer()) {
            return $this->selectedEvents()
                ->whereNotNull('first_introduction_year')
                ->with('taxon')
                ->orderByDesc('first_introduction_year')
                ->limit(5)
                ->get()
                ->toBase()
                ->map(fn (IntroEventRecord $event): array => ['event' => $event, 'year' => (int) $event->first_introduction_year]);
        }

        return $this->selectedRecords()
            ->whereNotNull('first_arrival_year')
            ->whereHas('introEvent.taxon')
            ->with('introEvent.taxon')
            ->orderByDesc('first_arrival_year')
            ->limit(5)
            ->get()
            ->toBase()
            ->map(function (SubregionRecord $record): array {
                /** @var IntroEventRecord $event never null: whereHas('introEvent.taxon') above */
                $event = $record->introEvent;

                return ['event' => $event, 'year' => (int) $record->first_arrival_year];
            });
    }

    /**
     * Approved, located occurrences of the filtered species, wherever they
     * are; nothing while the pins are off.
     *
     * @return list<int>
     */
    #[Computed]
    public function occurrenceIds(): array
    {
        if (! $this->pins) {
            return [];
        }

        return Occurrence::query()
            ->where('status', OccurrenceStatus::APPROVED)
            ->whereNotNull('location')
            ->whereIn('intro_event_record_id', $this->filteredEventIds())
            ->pluck('id')
            ->all();
    }

    /** Events of species still in the catalogue: the same base as the data page. */
    protected static function eventsQuery(): Builder
    {
        return IntroEventRecord::query()->whereHas('taxon');
    }

    /** Ids of the events the filters keep, before the map's own pick. */
    protected function filteredEventIds(): Builder
    {
        return $this->filterTableQuery(self::eventsQuery())->select('intro_event_records.id');
    }

    /**
     * Narrow events to the picked subregion or country of first record; a
     * country also matches the stored names reported under it.
     */
    protected function inSelectedArea(Builder $events): Builder
    {
        $storedNames = [(string) $this->country, ...array_keys(MediterraneanDashboard::REPORTED_AS, $this->country, true)];

        return $this->isCountryLayer()
            ? $events->where(function (Builder $query) use ($storedNames): void {
                foreach ($storedNames as $name) {
                    $query->orWhereJsonContains('first_country', $name);
                }
            })
            : $events->whereHas('subregionRecords', fn (Builder $query): Builder => $query->where('subregion', $this->subregion));
    }

    /** @return Builder<IntroEventRecord> the filtered events in the picked area */
    protected function selectedEvents(): Builder
    {
        return $this->inSelectedArea(IntroEventRecord::query()->whereIn('intro_event_records.id', $this->filteredEventIds()));
    }

    /** @return Builder<SubregionRecord> */
    protected function selectedRecords(): Builder
    {
        return SubregionRecord::query()
            ->where('subregion', $this->subregion)
            ->whereIn('intro_event_id', $this->filteredEventIds());
    }

    /**
     * Codes ticked in the EcAp subregion filter, once applied; empty means all.
     *
     * @return list<string>
     */
    protected function filteredSubregions(): array
    {
        return array_values(array_filter((array) ($this->tableFilters['subregion']['values'] ?? [])));
    }

    protected function allowsSubregion(string $code): bool
    {
        return $this->filteredSubregions() === [] || in_array($code, $this->filteredSubregions(), true);
    }

    public function render(): View
    {
        // A pick the filters have just ruled out moves to the first they allow:
        // the first ticked subregion, or the country with the most species.
        if (! $this->allowsSubregion($this->subregion)) {
            $this->subregion = $this->filteredSubregions()[0];
            $this->resetPage();
        }

        if ($this->isCountryLayer() && ($this->countryCounts[$this->country] ?? 0) === 0) {
            $this->country = array_key_first($this->countryCounts);
            $this->resetPage();
        }

        return view('livewire.nis-map')->extends('app')->section('content');
    }
}
