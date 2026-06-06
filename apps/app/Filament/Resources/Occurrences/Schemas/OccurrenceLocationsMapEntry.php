<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;

class OccurrenceLocationsMapEntry extends MapEntry
{
    public function getPickMarkerData(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Occurrence) {
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
            ->popupContent("{$record->taxon?->scientificname}<br>Lat: {$first['lat']}<br>Lng: {$first['lng']}");

        return $pickMarker->toArray();
    }
}
