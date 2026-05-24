<?php

namespace App\Casts;

use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Null-safe wrapper around the Coordinate value object cast.
 * The vendor cast crashes when the stored JSON value is null.
 */
class SafeCoordinateCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Coordinate
    {
        if ($value === null) {
            return null;
        }

        $coords = json_decode($value, true);

        if (! is_array($coords) || ! isset($coords['lat'], $coords['lng'])) {
            return null;
        }

        return new Coordinate((float) $coords['lat'], (float) $coords['lng']);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Coordinate) {
            return [$key => json_encode($value->toArray())];
        }

        if (is_array($value)) {
            $coordinate = Coordinate::fromArray($value);

            return [$key => $coordinate ? json_encode($coordinate->toArray()) : null];
        }

        return [$key => null];
    }
}
