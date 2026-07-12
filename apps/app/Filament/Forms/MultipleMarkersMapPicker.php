<?php

namespace App\Filament\Forms;

use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;

/**
 * Map picker field that enables draw-marker mode by default and computes
 * the initial map center from the first marker's coordinates when state
 * is available.
 */
class MultipleMarkersMapPicker extends MapPicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->drawMarkerControl(true);
    }

    /**
     * Disables default state casting so raw marker data is preserved.
     */
    public function getDefaultStateCasts(): array
    {
        return [];
    }

    protected function getMapCenter(): array
    {
        $state = $this->getState();

        if (! $state) {
            return parent::getMapCenter();
        }

        if (is_array($state) && isset($state[0]['lat'], $state[0]['lng'])) {
            return [
                'lat' => $state[0]['lat'] + 0.5 ** ($this->getDefaultZoom() - 4),
                'lng' => $state[0]['lng'],
            ];
        }

        return [
            'lat' => 38.0,
            'lng' => 23.0,
        ];
    }
}
