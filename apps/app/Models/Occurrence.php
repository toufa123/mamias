<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\CoverageMethod;
use App\Enums\CoverageUnit;
use App\Enums\OccurrenceStatus;
use App\Models\Traits\HasSpatialLocation;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Species occurrence record linked to an introduction event.
 *
 * Records a specific observation of a non-indigenous species at a location,
 * with ACFOR abundance scale, habitat data, and moderation status.
 *
 * @property int $id
 * @property int $user_id
 * @property int $intro_event_record_id
 * @property array|null $location
 * @property float|null $depth
 * @property AcforScale|null $acfor_scale
 * @property float|null $coverage_value
 * @property CoverageUnit|null $coverage_unit
 * @property CoverageMethod|null $coverage_method
 * @property array|null $habitats
 * @property array|null $photo_paths
 * @property string|null $notes
 * @property Carbon|null $observed_at
 * @property OccurrenceStatus $status
 * @property string|null $moderation_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo user()
 * @method BelongsTo introEventRecord()
 * @method HasOneThrough taxon()
 */
#[Fillable([
    'user_id',
    'intro_event_record_id',
    'location',
    'location_point',
    'depth',
    'acfor_scale',
    'coverage_value',
    'coverage_unit',
    'coverage_method',
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
            'coverage_value' => 'float',
            'coverage_unit' => CoverageUnit::class,
            'coverage_method' => CoverageMethod::class,
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

    /**
     * Configure activity logging to track all attribute changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The user who submitted this occurrence record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The introduction event record this occurrence belongs to.
     */
    public function introEventRecord(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class);
    }

    /**
     * The taxon associated with this occurrence via the introduction event.
     */
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
