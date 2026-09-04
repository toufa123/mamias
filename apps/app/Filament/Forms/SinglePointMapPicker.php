<?php

namespace App\Filament\Forms;

use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;

/**
 * Map picker that holds exactly one point: click the map to place the pin,
 * click again to move it.
 *
 * The draw-marker toolbar stays off on purpose. Geoman's drawn markers never
 * reach the field state — only the map click does — so the toolbar let a
 * reporter scatter pins that were silently dropped on save.
 *
 * State is kept as a raw `['lat' => …, 'lng' => …]` array, which is what the
 * package's JS reads. Records written before this field existed hold a list of
 * points, so hydration takes the first one and dehydration always writes a
 * single point back; CoordinatesCast wraps it into the stored JSON list.
 */
class SinglePointMapPicker extends MapPicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->drawMarkerControl(false);

        // Registered after parent::setUp(), so it runs after the parent's own
        // hydration callback has pulled the raw attribute off the record.
        $this->afterStateHydrated(static function (self $component): void {
            $component->state(self::toSinglePoint($component->getState()));
        });

        // Dehydrated as a one-element list, which is the shape CoordinatesCast
        // writes to the json column and the shape older rows already hold. The
        // field state itself stays a bare point, because that is what the
        // package's JS reads.
        $this->dehydrateStateUsing(static function (mixed $state): ?array {
            $point = self::toSinglePoint($state);

            return $point === null ? null : [$point];
        });
    }

    /**
     * Disables the package's Coordinate state cast so the state stays a plain
     * array, matching what the field's JS reads and writes.
     */
    public function getDefaultStateCasts(): array
    {
        return [];
    }

    /**
     * Reduces any accepted shape — a Coordinate, a single point, or a list of
     * points — to one `['lat' => float, 'lng' => float]` array.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function toSinglePoint(mixed $state): ?array
    {
        if ($state instanceof Coordinate) {
            $state = $state->toArray();
        }

        if (! is_array($state)) {
            return null;
        }

        $point = isset($state['lat'], $state['lng'])
            ? $state
            : collect($state)->first(fn (mixed $candidate): bool => is_array($candidate) && isset($candidate['lat'], $candidate['lng']));

        if (! is_array($point)) {
            return null;
        }

        return [
            'lat' => (float) $point['lat'],
            'lng' => (float) $point['lng'],
        ];
    }

    protected function getMapCenter(): array
    {
        $point = self::toSinglePoint($this->getState());

        if ($point === null) {
            return parent::getMapCenter();
        }

        return [
            // Nudged north by roughly half a screen so the pin sits below the
            // modal's header rather than under it, mirroring the sibling picker.
            'lat' => $point['lat'] + 0.5 ** ($this->getDefaultZoom() - 4),
            'lng' => $point['lng'],
        ];
    }
}
