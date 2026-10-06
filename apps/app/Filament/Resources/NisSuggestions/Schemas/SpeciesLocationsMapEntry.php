<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Models\NisSuggestion;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;

/**
 * Custom MapEntry that renders all NisSuggestion locations for the same
 * suggested species name as gray markers, plus the current record's
 * location as a red marker.
 */
class SpeciesLocationsMapEntry extends MapEntry
{
    /**
     * `location` is a list of points (CoordinatesCast); older rows hold a single
     * {lat, lng}. The package's own centre/marker code only reads the latter.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function firstPoint(mixed $coords): ?array
    {
        $point = is_array($coords) && array_is_list($coords) ? ($coords[0] ?? null) : $coords;

        return is_array($point) && isset($point['lat'], $point['lng'])
            ? ['lat' => (float) $point['lat'], 'lng' => (float) $point['lng']]
            : null;
    }

    /** @return array{lat: float, lng: float} */
    protected function getMapCenter(): array
    {
        $point = self::firstPoint($this->getRecord()?->location);

        return $point ?? $this->getParentMapCenter();
    }

    /** @return array<int, Marker> */
    protected function getMarkers(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof NisSuggestion || ! $record->suggested_scientific_name) {
            return [];
        }

        $records = NisSuggestion::query()
            ->where('suggested_scientific_name', $record->suggested_scientific_name)
            ->whereKeyNot($record->getKey())
            ->whereNotNull('location')
            ->get();

        return $records
            ->map(function (NisSuggestion $other): ?Marker {
                $point = self::firstPoint(json_decode($other->getRawOriginal('location'), true));

                if ($point === null) {
                    return null;
                }

                ['lat' => $lat, 'lng' => $lng] = $point;

                return Marker::make((float) $lat, (float) $lng)
                    ->gray()
                    ->tooltipContent("{$lat}, {$lng}")
                    ->tooltipOptions(['direction' => 'top'])
                    ->popupContent("{$other->suggested_scientific_name}<br>Lat: {$lat}<br>Lng: {$lng}");
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed> The pick marker configuration array for the current record's location.
     */
    public function getPickMarkerData(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof NisSuggestion) {
            return parent::getPickMarkerData();
        }

        $first = self::firstPoint($record->location);

        if ($first === null) {
            return parent::getPickMarkerData();
        }

        $pickMarker = Marker::make((float) $first['lat'], (float) $first['lng'])
            ->red()
            ->tooltipContent("{$first['lat']}, {$first['lng']}")
            ->tooltipOptions(['direction' => 'top'])
            ->popupContent("{$record->suggested_scientific_name}<br>Lat: {$first['lat']}<br>Lng: {$first['lng']}");

        return $pickMarker->toArray();
    }
}
