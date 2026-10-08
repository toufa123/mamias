<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Presence of a species in one country, linked to its introduction event.
 *
 * Unlike the event's `first_country` (where the species was first recorded
 * in the Mediterranean), these rows list every country it has been recorded
 * in since, each with its own establishment status and first-record year.
 *
 * @property int $id
 * @property int $intro_event_id
 * @property string $country
 * @property EstablishmentStatus|null $establishment_status
 * @property int|null $first_record_year
 * @property int|null $literature_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo<IntroEventRecord, $this> introEvent()
 * @method BelongsTo literature()
 */
#[Fillable(['intro_event_id', 'country', 'establishment_status', 'first_record_year', 'literature_id', 'notes'])]
class CountryRecord extends Model
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
     * The introduction event this country record belongs to.
     */
    public function introEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'intro_event_id');
    }

    /**
     * The reference documenting the species in this country.
     */
    public function literature(): BelongsTo
    {
        return $this->belongsTo(Literature::class);
    }

    protected function casts(): array
    {
        return [
            'establishment_status' => EstablishmentStatus::class,
            'first_record_year' => 'integer',
        ];
    }
}
