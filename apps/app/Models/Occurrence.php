<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\OccurrenceStatus;
use App\Models\Traits\HasSpatialLocation;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'intro_event_record_id',
    'location',
    'location_point',
    'depth',
    'acfor_scale',
    'habitats',
    'photo_paths',
    'notes',
    'observed_at',
    'status',
    'moderation_notes',
])]
class Occurrence extends Model
{
    use HasFactory, HasSpatialLocation, LogsActivity;

    protected function casts(): array
    {
        return [
            'location' => CoordinatesCast::class,
            'depth' => 'float',
            'acfor_scale' => AcforScale::class,
            'habitats' => 'array',
            'photo_paths' => 'array',
            'observed_at' => 'datetime',
            'status' => OccurrenceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Occurrence $occurrence): void {
            if (! $occurrence->isDirty('location')) {
                return;
            }

            $coords = $occurrence->location;
            $first = is_array($coords) ? ($coords[0] ?? null) : $coords;

            $occurrence->location_point = $first && isset($first['lat'], $first['lng'])
                ? Point::makeGeodetic((float) $first['lat'], (float) $first['lng'])
                : null;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function introEventRecord(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class);
    }

    public function taxon(): HasOneThrough
    {
        return $this->hasOneThrough(
            Taxon::class,
            IntroEventRecord::class,
            'id',
            'id',
            'intro_event_record_id',
            'taxon_id',
        );
    }
}
