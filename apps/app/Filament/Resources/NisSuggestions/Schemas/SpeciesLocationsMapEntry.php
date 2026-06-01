<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Models\NisSuggestion;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;

class SpeciesLocationsMapEntry extends MapEntry
{
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
                $coords = json_decode($other->getRawOriginal('location'), true);
                $lat = $coords['lat'] ?? null;
                $lng = $coords['lng'] ?? null;

                if ($lat === null || $lng === null) {
                    return null;
                }

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

    public function getPickMarkerData(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof NisSuggestion) {
            return parent::getPickMarkerData();
        }

        $coords = $record->location;
        $first = is_array($coords) ? ($coords[0] ?? null) : $coords;

        if (! $first || ! isset($first['lat'], $first['lng'])) {
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
