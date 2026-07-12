<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a JSON column storing coordinate arrays to a structured PHP array and back.
 *
 * Expects both single-object `{"lat":..., "lng":...}` and multi-object `[{...}, {...}]` JSON.
 */
class CoordinatesCast implements CastsAttributes
{
    /**
     * Decode the stored JSON value into an array of coordinate objects.
     *
     * @return array<int, array{lat: float, lng: float}>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['lat'], $decoded['lng'])) {
            return [$decoded];
        }

        return array_values(array_filter($decoded, fn ($c) => is_array($c) && isset($c['lat'], $c['lng'])));
    }

    /**
     * Transform the value to a JSON string for storage.
     *
     * @param  array<int, array{lat: float, lng: float}>|null  $value
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return [$key => null];
        }

        if (! is_array($value)) {
            return [$key => null];
        }

        $value = array_values(array_filter($value, fn ($c) => is_array($c) && isset($c['lat'], $c['lng'])));

        return [$key => empty($value) ? null : json_encode($value)];
    }
}
