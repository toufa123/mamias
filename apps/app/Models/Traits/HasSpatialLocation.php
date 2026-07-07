<?php

namespace App\Models\Traits;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSpatialLocation
{
    public function initializeHasSpatialLocation(): void
    {
        $this->casts['location_point'] = Point::class;
    }

    public function scopeNear(Builder $query, float $lat, float $lng, float $meters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location_point::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $meters]
        );
    }

    public function scopeWithinBoundingBox(Builder $query, float $south, float $west, float $north, float $east): Builder
    {
        return $query
            ->whereRaw(
                'location_point && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$west, $south, $east, $north]
            )
            ->whereRaw(
                'ST_Intersects(location_point, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
                [$west, $south, $east, $north]
            );
    }

    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->orderByRaw(
            'location_point <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)',
            [$lng, $lat]
        );
    }

    public function scopeWithDistanceFrom(Builder $query, float $lat, float $lng): Builder
    {
        return $query->addSelect(DB::raw(
            "ST_Distance(location_point::geography, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance_meters"
        ));
    }
}
