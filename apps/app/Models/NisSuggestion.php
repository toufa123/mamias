<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\LiteratureStatus;
use App\Models\Traits\HasSpatialLocation;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * NIS (Non-Indigenous Species) Suggestion — a user-submitted species observation
 * for review and potential inclusion in the catalogue.
 *
 * Includes spatial location (lat/lng + PostGIS point), ACFOR abundance scale,
 * habitat types, and supporting media (photos, documents). Suggestions go through
 * a moderation workflow before being linked to a Taxon.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $aphia_id
 * @property string $suggested_scientific_name
 * @property string|null $authority
 * @property string|null $worms_status
 * @property string|null $suggested_common_name
 * @property array|null $location
 * @property float|null $depth
 * @property string|null $kingdom
 * @property AcforScale|null $acfor_scale
 * @property array|null $habitats
 * @property array|null $photo_paths
 * @property array|null $document_paths
 * @property LiteratureStatus $status
 * @property string|null $rejection_reason
 * @property int|null $taxon_id
 * @property int|null $resubmitted_from_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method BelongsTo user()
 * @method BelongsToMany literatures()
 * @method BelongsTo taxon()
 * @method BelongsTo resubmittedFrom()
 * @method HasMany resubmissions()
 */
#[Table('nis_suggestions')]
#[Fillable([
    'user_id',
    'aphia_id',
    'suggested_scientific_name',
    'authority',
    'worms_status',
    'suggested_common_name',
    'location',
    'location_point',
    'depth',
    'kingdom',
    'acfor_scale',
    'habitats',
    'photo_paths',
    'document_paths',
    'status',
    'rejection_reason',
    'taxon_id',
    'resubmitted_from_id',
])]
class NisSuggestion extends Model
{
    use HasFactory, HasSpatialLocation, LogsActivity, SoftDeletes, Userstamps;

    protected function casts(): array
    {
        return [
            'aphia_id' => 'integer',
            'acfor_scale' => AcforScale::class,
            'kingdom' => 'string',
            'habitats' => 'array',
            'photo_paths' => 'array',
            'document_paths' => 'array',
            'status' => LiteratureStatus::class,
            'depth' => 'float',
            'location' => CoordinatesCast::class,
        ];
    }

    /**
     * Configure activity logging to track all attribute changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept(['location_point']);
    }

    /**
     * The user who submitted this NIS suggestion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Literature references linked to this suggestion via the pivot table.
     */
    public function literatures(): BelongsToMany
    {
        return $this->belongsToMany(Literature::class, 'nis_suggestion_literature')->withTimestamps();
    }

    /**
     * The accepted taxon record that this suggestion was matched to.
     */
    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    /**
     * The original suggestion that this is a resubmission of.
     */
    public function resubmittedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resubmitted_from_id');
    }

    /**
     * Subsequent resubmissions of this suggestion.
     */
    public function resubmissions(): HasMany
    {
        return $this->hasMany(self::class, 'resubmitted_from_id');
    }

    protected static function booted(): void
    {
        static::saving(function (NisSuggestion $suggestion): void {
            foreach (['suggested_scientific_name', 'authority', 'suggested_common_name'] as $field) {
                if ($suggestion->$field !== null) {
                    $suggestion->$field = strip_tags($suggestion->$field);
                }
            }

            if (! $suggestion->isDirty('location')) {
                return;
            }

            $coords = $suggestion->location;
            $first = is_array($coords) ? ($coords[0] ?? null) : $coords;

            $suggestion->location_point = $first && isset($first['lat'], $first['lng'])
                ? Point::makeGeodetic((float) $first['lat'], (float) $first['lng'])
                : null;
        });
    }
}
