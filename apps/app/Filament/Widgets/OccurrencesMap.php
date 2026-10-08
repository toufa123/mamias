<?php

namespace App\Filament\Widgets;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Pages\ListOccurrences;
use App\Models\Occurrence;
use App\Models\Taxon;
use EduardoRibeiroDev\FilamentLeaflet\Layers\BaseLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Widgets\MapWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

/**
 * Every occurrence the Occurrences table currently shows — same filters,
 * search and tab — one pin each, coloured by review status like the status
 * badge. Clicking a pin opens that row's view modal, where it is approved or
 * rejected.
 */
class OccurrencesMap extends MapWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.occurrences-map';

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Occurrences map — green approved · gray pending · red rejected';

    protected array|\Closure|null $mapCenter = [36, 14];

    protected int|\Closure $defaultZoom = 4;

    protected int|\Closure $maxZoom = 10;

    protected int|\Closure $mapHeight = 420;

    /** The UNEP/MAP basemap (config/filament-leaflet.php), like every other map. */
    protected function getTileLayersUrl(): array
    {
        return [config('filament-leaflet.basemap.label') => config('filament-leaflet.basemap.url')];
    }

    protected function getTablePage(): string
    {
        return ListOccurrences::class;
    }

    /**
     * The table re-renders this widget when its filters change, but the map
     * is drawn client-side once, so push the new pins on every render.
     */
    public function rendering(): void
    {
        $this->refreshMap();
    }

    #[On('occurrence-moderated')]
    public function refreshAfterModeration(): void
    {
        $this->refreshMap();
    }

    /** Pins carry only their id: a bound model holds a PostGIS point Livewire cannot serialize. */
    public function handleLayerClick(string|BaseLayer $layerId): void
    {
        $id = is_string($layerId) ? $layerId : (string) $layerId->getId();

        if (preg_match('/^occurrence-(\d+)$/', $id, $match)) {
            $this->dispatch('open-occurrence', id: (int) $match[1])->to(ListOccurrences::class);
        }
    }

    /**
     * @return array<int, Marker>
     */
    protected function getMarkers(): array
    {
        // ponytail: every point the table matches, in one payload; cluster or bound-box query once there are thousands.
        /** @var Builder<Occurrence> $query */
        $query = $this->getPageTableQuery();

        return $query
            ->with('taxon')
            ->whereNotNull('location_point')
            ->get()
            ->map(function (Occurrence $occurrence): ?Marker {
                $point = $occurrence->location[0] ?? null;

                if (! isset($point['lat'], $point['lng'])) {
                    return null;
                }

                /** @var Taxon|null $taxon */
                $taxon = $occurrence->taxon;

                $details = array_filter([
                    $taxon?->scientificname,
                    $occurrence->status->getLabel(),
                    $occurrence->observed_at?->format('Y-m-d'),
                    $occurrence->depth !== null ? "{$occurrence->depth} m" : null,
                    $occurrence->acfor_scale?->getLabel(),
                ]);

                $marker = Marker::make((float) $point['lat'], (float) $point['lng'])
                    ->id("occurrence-{$occurrence->getKey()}")
                    ->tooltipContent(e(implode(' · ', $details)))
                    ->tooltipOptions(['direction' => 'top']);

                return self::colourByStatus($marker, $occurrence->status);
            })
            ->filter()
            ->values()
            ->all();
    }

    /** Green approved, gray pending, red rejected: the same on every occurrence map. */
    public static function colourByStatus(Marker $marker, OccurrenceStatus $status): Marker
    {
        return match ($status) {
            OccurrenceStatus::APPROVED => $marker->green(),
            OccurrenceStatus::PENDING => $marker->gray(),
            OccurrenceStatus::REJECTED => $marker->red(),
        };
    }
}
