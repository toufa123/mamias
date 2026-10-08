<?php

namespace App\Livewire;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordInfolist;
use App\Filament\Resources\Taxons\Schemas\TaxonInfolist;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Public page for one introduction event (/pages/data/{event}): the species'
 * MAMIAS catalogue entry, the event itself, and its approved occurrences on a
 * map. Bound to the event rather than the taxon because occurrences hang off
 * the event, so the page shows exactly the row it was opened from.
 */
class NisSpecies extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public IntroEventRecord $introEventRecord;

    public function mount(IntroEventRecord $introEventRecord): void
    {
        // Same rule as the data table: a species deleted from the catalogue has no page.
        abort_if($introEventRecord->taxon === null, 404);

        $this->introEventRecord = $introEventRecord;
    }

    public function catalogueInfolist(Schema $schema): Schema
    {
        return TaxonInfolist::configurePublic($schema->record($this->introEventRecord->taxon));
    }

    public function introEventInfolist(Schema $schema): Schema
    {
        return IntroEventRecordInfolist::configurePublic($schema->record($this->introEventRecord));
    }

    public function occurrencesInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->introEventRecord)
            ->components([
                // No state: every occurrence is a marker, so there is no single pick pin.
                MapEntry::make('occurrence_map')
                    ->hiddenLabel()
                    ->state(null)
                    ->height(480)
                    ->center(35, 18)
                    ->zoom(3)
                    ->fitBounds()
                    ->markers(fn (): array => $this->occurrenceMarkers()),
            ]);
    }

    /**
     * Approved occurrences only: pending and rejected reports are not public.
     *
     * @return Collection<int, Occurrence>
     */
    #[Computed]
    public function occurrences(): Collection
    {
        return $this->introEventRecord->occurrences()
            ->where('status', OccurrenceStatus::APPROVED)
            ->whereNotNull('location')
            ->orderBy('observed_at')
            ->get();
    }

    /**
     * @return list<Marker>
     */
    protected function occurrenceMarkers(): array
    {
        $name = (string) $this->introEventRecord->taxon?->scientificname;

        return $this->occurrences
            ->map(fn (Occurrence $occurrence): ?Marker => self::occurrenceMarker($occurrence, $name))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * One pin at the occurrence's first coordinate, or none without one. The
     * plugin's popups are raw HTML, and habitats are submitted by the public,
     * so every value is escaped. Shared with the map page (App\Livewire\NisAreaMap).
     */
    public static function occurrenceMarker(Occurrence $occurrence, string $scientificName): ?Marker
    {
        $coords = $occurrence->location;
        $first = is_array($coords) ? ($coords[0] ?? null) : null;

        if (! $first || ! isset($first['lat'], $first['lng'])) {
            return null;
        }

        $lat = (float) $first['lat'];
        $lng = (float) $first['lng'];

        $rows = array_filter([
            'Observed' => $occurrence->observed_at?->format('d/m/Y'),
            'Coordinates' => sprintf('%.5f, %.5f', $lat, $lng),
            'Depth' => $occurrence->depth !== null ? "{$occurrence->depth} m" : null,
            'Abundance (ACFOR)' => $occurrence->acfor_scale?->getLabel(),
            'Habitats' => filled($occurrence->habitats) ? implode(', ', (array) $occurrence->habitats) : null,
        ], filled(...));

        $popup = '<em>'.e($scientificName).'</em>'.implode('', array_map(
            fn (string $label, string $value): string => '<br><strong>'.e($label).':</strong> '.e($value),
            array_keys($rows),
            $rows,
        ));

        return Marker::make($lat, $lng)
            ->red()
            ->tooltipContent($occurrence->observed_at?->format('d/m/Y') ?? 'Occurrence')
            ->popupContent($popup);
    }

    public function render(): View
    {
        return view('livewire.nis-species', [
            'taxon' => $this->introEventRecord->taxon,
            'occurrenceCount' => $this->occurrences->count(),
        ])->extends('app')->section('content');
    }
}
