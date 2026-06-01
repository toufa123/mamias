<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CoordinatesCast implements CastsAttributes
{
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
