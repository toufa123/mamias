<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Subregion record detailing introduction status within a Mediterranean subregion.
 *
 * Tracks per-subregion NIS status and first arrival year for a species
 * linked to an introduction event.
 *
 * @property int $id
 * @property int $intro_event_id
 * @property Subregion $subregion
 * @property NisStatus|null $nis_status
 * @property EstablishmentStatus|null $establishment_status
 * @property int|null $first_arrival_year
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo introEvent()
 */
#[Fillable(['intro_event_id', 'subregion', 'nis_status', 'establishment_status', 'first_arrival_year', 'notes'])]
class SubregionRecord extends Model
{
    use HasFactory, LogsActivity, Userstamps;

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
     * The introduction event this subregion record belongs to.
     */
    public function introEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'intro_event_id');
    }

    protected function casts(): array
    {
        return [
            'subregion' => Subregion::class,
            'nis_status' => NisStatus::class,
            'establishment_status' => EstablishmentStatus::class,
        ];
    }
}
