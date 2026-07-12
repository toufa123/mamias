<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Introduction Event Record for a non-indigenous species.
 *
 * Tracks the first known introduction of a species into a region,
 * including its NIS status, establishment status, and linked
 * subregion/pathway/occurrence records.
 *
 * @property int $id
 * @property int $taxon_id
 * @property int|null $literature_id
 * @property int|null $first_introduction_year
 * @property array|null $first_country
 * @property NisStatus $nis_status
 * @property EstablishmentStatus $establishment_status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo taxon()
 * @method BelongsTo literature()
 * @method HasMany subregionRecords()
 * @method HasMany pathwayRecords()
 * @method HasMany occurrences()
 * @method HasMany eicatAssessments()
 */
#[Fillable(['taxon_id', 'first_introduction_year', 'first_country', 'nis_status', 'establishment_status', 'literature_id', 'notes'])]
class IntroEventRecord extends Model
{
    use HasFactory, LogsActivity, Userstamps;

    protected function casts(): array
    {
        return [
            'first_country' => 'array',
            'nis_status' => NisStatus::class,
            'establishment_status' => EstablishmentStatus::class,
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
            ->dontLogEmptyChanges();
    }

    /**
     * The taxon (species) associated with this introduction event.
     */
    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    /**
     * The literature reference for this introduction event.
     */
    public function literature(): BelongsTo
    {
        return $this->belongsTo(Literature::class);
    }

    /**
     * Subregion records detailing introduction status per Mediterranean subregion.
     */
    public function subregionRecords(): HasMany
    {
        return $this->hasMany(SubregionRecord::class, 'intro_event_id');
    }

    /**
     * Pathway records describing how the species was introduced.
     */
    public function pathwayRecords(): HasMany
    {
        return $this->hasMany(PathwayRecord::class, 'intro_event_id');
    }

    /**
     * Occurrence records linked to this introduction event.
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class, 'intro_event_record_id');
    }

    /**
     * EICAT impact assessments for this introduction event.
     */
    public function eicatAssessments(): HasMany
    {
        return $this->hasMany(EicatAssessment::class);
    }
}
