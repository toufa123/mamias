<?php

namespace App\Models\Traits;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Adds spatial location query scopes to a model using PostGIS (via Magellan).
 *
 * Requires a `location_point` geometry column on the model's table.
 */
trait HasSpatialLocation
{
    /**
     * Boot the trait – register the `location_point` cast to a Magellan Point.
     */
    public function initializeHasSpatialLocation(): void
    {
        $this->casts['location_point'] = Point::class;
    }

    /**
     * Scope to records within a given radius (in meters) from a point.
     *
     * @return Builder<static>
     */
    public function scopeNear(Builder $query, float $lat, float $lng, float $meters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location_point::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $meters]
        );
    }

    /**
     * Scope to records that fall inside a rectangular bounding box.
     *
     * @return Builder<static>
     */
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

    /**
     * Order query results by proximity to a given point.
     *
     * @return Builder<static>
     */
    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->orderByRaw(
            'location_point <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)',
            [$lng, $lat]
        );
    }

    /**
     * Add a computed `distance_meters` column for the distance from a given point.
     *
     * @return Builder<static>
     */
    public function scopeWithDistanceFrom(Builder $query, float $lat, float $lng): Builder
    {
        return $query->addSelect(DB::raw(
            "ST_Distance(location_point::geography, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance_meters"
        ));
    }
}
