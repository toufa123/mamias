<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;

/**
 * Custom MapEntry that renders the occurrence's location as a red
 * pick marker with taxon scientific name in the popup.
 */
class OccurrenceLocationsMapEntry extends MapEntry
{
    /**
     * @return array<string, mixed> The pick marker configuration array for the occurrence location.
     */
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
