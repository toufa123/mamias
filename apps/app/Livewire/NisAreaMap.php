<?php

namespace App\Livewire;

use App\Enums\Subregion;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Layers\BaseLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Shapes\Polygon;
use EduardoRibeiroDev\FilamentLeaflet\Widgets\MapWidget;
use Livewire\Attributes\Reactive;

/**
 * The map on App\Livewire\NisMap, one of two layers at a time on the UNEP/MAP
 * basemap, plus optional occurrence pins:
 *
 * - subregions: the four EcAp subregions of the Barcelona Convention
 *   (UNEP/MAP), shaded by species count, credited to UNEP/MAP on the page.
 *   The outlines in resources/geo/ecap-subregions.geojson (simplified to
 *   about 2 km) were traced from the EEA's MSFD Mediterranean subregions,
 *   which share the four EcAp names; swap the file for UNEP/MAP's own
 *   boundaries when INFO/RAC publishes them.
 * - countries: one ECharts bubble per country of first record, at the
 *   country's centre, or near its coast when the centre lies far inland
 *   (COUNTRY_ANCHORS), sized by species count. Barcelona
 *   Convention maps avoid national boundaries, so a country is never an
 *   outline. resources/js/nis-area-map.js draws the bubbles over this map
 *   from bubbles(), and reports a click as "country-picked".
 *
 * The page owns the counts and the selection; a click is reported back to it.
 */
class NisAreaMap extends MapWidget
{
    /** Light to dark: more species, darker sea. */
    public const SCALE = ['#d4ebe7', '#94cbc3', '#4fa69c', '#2f8a83', '#134f4c'];

    public const EMPTY = '#d1d5db';

    /**
     * Where each country's bubble sits, [lat, lng]: its geographic centre
     * (CIA World Factbook) when that lies within about 100 km of the
     * Mediterranean, otherwise a point on land near its Mediterranean coast,
     * so every bubble reads as a Mediterranean country. Keyed by country name:
     * the Barcelona Convention parties, the names NisMap counts under
     * (MediterraneanDashboard::REPORTED_AS folds the others in). A name
     * missing here is not drawn.
     *
     * @var array<string, array{float, float}>
     */
    public const COUNTRY_ANCHORS = [
        'Albania' => [41.0, 20.0],
        'Algeria' => [36.4, 3.2], // centre 28.0, 3.0 is in the Sahara; near Algiers
        'Bosnia and Herzegovina' => [43.2, 17.7], // centre 44.0, 18.0; near Neum
        'Croatia' => [43.8, 15.9], // centre 45.17, 15.5; Dalmatia, near Šibenik
        'Cyprus' => [35.0, 33.0],
        'Egypt' => [31.0, 30.5], // centre 27.0, 30.0; the Nile Delta
        'France' => [43.6, 4.6], // centre 46.0, 2.0; Provence, near the Camargue
        'Greece' => [39.0, 22.0],
        'Israel' => [31.5, 34.75],
        'Italy' => [42.83, 12.83],
        'Lebanon' => [33.83, 35.83],
        'Libya' => [31.0, 16.6], // centre 25.0, 17.0; near Sirte
        'Malta' => [35.83, 14.58],
        'Monaco' => [43.73, 7.4],
        'Montenegro' => [42.5, 19.3],
        'Morocco' => [35.0, -4.0], // centre 32.0, -5.0; the Rif coast, near Al Hoceima
        'Slovenia' => [45.55, 13.8], // centre 46.12, 14.82; near Koper
        'Spain' => [39.4, -0.5], // centre 40.0, -4.0; near Valencia
        'Syria' => [35.3, 36.0], // centre 35.0, 38.0; near Latakia
        'Tunisia' => [35.5, 10.4], // centre 34.0, 9.0; the Sahel, near Sousse
        'Türkiye' => [37.0, 31.5], // centre 39.0, 35.0; near Antalya
    ];

    protected static bool $isDiscovered = false;

    protected string $view = 'livewire.nis-area-map';

    /** 'subregions' or 'countries' */
    #[Reactive]
    public string $layer = 'subregions';

    /** @var array<string, int> species per subregion code */
    #[Reactive]
    public array $counts = [];

    /** @var array<string, int> species per country of first record */
    #[Reactive]
    public array $countryCounts = [];

    /** The selected subregion code or country name, on the active layer. */
    #[Reactive]
    public string $selected = '';

    /** @var list<int> approved occurrences to pin; empty when pins are off */
    #[Reactive]
    public array $occurrenceIds = [];

    protected int|\Closure $mapHeight = 520;

    protected int|\Closure $maxZoom = 8;

    /** The UNEP/MAP basemap (config/filament-leaflet.php), like every other map. */
    protected function getTileLayersUrl(): array
    {
        return [config('filament-leaflet.basemap.label') => config('filament-leaflet.basemap.url')];
    }

    /**
     * The map is drawn client-side once, so push the new layers on every
     * render — the page re-renders this widget whenever anything changes.
     */
    public function rendering(): void
    {
        $this->refreshMap();
        $this->dispatch('nis-bubbles', bubbles: $this->bubbles());
    }

    public function handleLayerClick(string|BaseLayer $layerId): void
    {
        $id = is_string($layerId) ? $layerId : (string) $layerId->getId();

        if (preg_match('/^subregion-([A-Z]+)-/', $id, $match) && Subregion::tryFrom($match[1])) {
            $this->dispatch('subregion-picked', code: $match[1]);
        }
    }

    /**
     * @return list<Marker>
     */
    protected function getMarkers(): array
    {
        if ($this->occurrenceIds === []) {
            return [];
        }

        return Occurrence::query()
            ->whereKey($this->occurrenceIds)
            ->with('introEventRecord.taxon')
            ->get()
            ->map(fn (Occurrence $occurrence): ?Marker => NisSpecies::occurrenceMarker($occurrence, (string) $occurrence->introEventRecord?->taxon?->scientificname)
                ?->id("occurrence-{$occurrence->getKey()}"))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The subregions are Leaflet shapes; the countries are ECharts bubbles,
     * drawn by the browser from bubbles().
     *
     * @return list<Polygon>
     */
    protected function getShapes(): array
    {
        return $this->layer === 'countries' ? [] : $this->subregionShapes();
    }

    /**
     * One bubble per country with species, biggest first; none off the
     * countries layer.
     *
     * @return list<array{name: string, label: string, lat: float, lng: float, value: int, selected: bool}>
     */
    public function bubbles(): array
    {
        if ($this->layer !== 'countries') {
            return [];
        }

        return collect($this->countryCounts)
            ->filter(fn (int $count, string $name): bool => $count > 0 && isset(self::COUNTRY_ANCHORS[$name]))
            ->sortDesc()
            ->map(fn (int $count, string $name): array => [
                'name' => $name,
                'label' => IntroEventRecordsTable::countryName($name),
                'lat' => self::COUNTRY_ANCHORS[$name][0],
                'lng' => self::COUNTRY_ANCHORS[$name][1],
                'value' => $count,
                'selected' => $name === $this->selected,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<Polygon>
     */
    protected function subregionShapes(): array
    {
        $max = max([1, ...array_values($this->counts)]);

        return collect(self::geometry())
            ->map(function (array $rings, string $code) use ($max): Polygon {
                $count = $this->counts[$code] ?? 0;
                // None left (filtered out, or no records): grey, not the lightest teal.
                $shade = $count === 0 ? self::EMPTY : self::SCALE[(int) ceil($count / $max * count(self::SCALE)) - 1];
                $isSelected = $code === $this->selected;

                // The plugin only adds layers whose id it has not drawn yet, so a
                // restyled shape needs a new id: the shade and selection are in it.
                return Polygon::make($rings)
                    ->id("subregion-{$code}-".substr(md5($shade.$isSelected), 0, 8))
                    ->fillColor($shade)
                    ->fillOpacity(0.75)
                    ->color($isSelected ? '#0b1f26' : '#ffffff')
                    ->weight($isSelected ? 3 : 1)
                    ->tooltipContent(e(Subregion::from($code)->getLabel()).': '.trans_choice(':count species|:count species', $count));
            })
            ->values()
            ->all();
    }

    /**
     * Rings per subregion code as Leaflet wants them: [lat, lng], outer ring
     * first, then the island holes.
     *
     * @return array<string, list<list<array{float, float}>>>
     */
    public static function geometry(): array
    {
        return once(function (): array {
            $collection = json_decode((string) file_get_contents(resource_path('geo/ecap-subregions.geojson')), true);

            return collect($collection['features'])
                ->mapWithKeys(fn (array $feature): array => [
                    $feature['id'] => array_map(
                        fn (array $ring): array => array_map(fn (array $point): array => [$point[1], $point[0]], $ring),
                        $feature['geometry']['coordinates'][0],
                    ),
                ])
                ->all();
        });
    }
}
