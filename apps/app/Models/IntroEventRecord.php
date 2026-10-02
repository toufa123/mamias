<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
 * @property string|null $verbatim_name Name the record was published under, kept across renames
 * @property int|null $first_introduction_year
 * @property array|null $first_country
 * @property NisStatus $nis_status
 * @property EstablishmentStatus $establishment_status
 * @property string|null $notes
 * @property string|null $pathway_check Pathway disagreement with EASIN; null when nothing to check
 * @property array{decision: string, detail: string, easin_id: ?string, check: ?string}|null $pathway_resolution How the last pathway check was settled
 * @property Carbon|null $pathway_checked_at When the last pathway check was settled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo<Taxon, $this> taxon()
 * @method BelongsTo<Literature, $this> literature()
 * @method HasMany<SubregionRecord, $this> subregionRecords()
 * @method HasMany<CountryRecord, $this> countryRecords()
 * @method HasMany<PathwayRecord, $this> pathwayRecords()
 * @method HasMany occurrences()
 * @method HasMany eicatAssessments()
 */
#[Fillable(['taxon_id', 'verbatim_name', 'first_introduction_year', 'first_country', 'nis_status', 'establishment_status', 'literature_id', 'notes', 'pathway_check'])]
class IntroEventRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Userstamps;

    protected function casts(): array
    {
        return [
            'first_country' => 'array',
            'pathway_resolution' => 'array',
            'pathway_checked_at' => 'datetime',
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
     * Every country the species has been recorded in, beyond the first one.
     */
    public function countryRecords(): HasMany
    {
        return $this->hasMany(CountryRecord::class, 'intro_event_id');
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
     * Events that count in the validated NIS baseline (Galanidi et al. 2023):
     * NIS, plus events nobody has classified yet. Cryptogenic, questionable,
     * range-expanding and data-deficient species are kept in the catalogue
     * but stay out of the figures.
     */
    #[Scope]
    protected function baseline(Builder $query): void
    {
        $query->where(fn (Builder $query): Builder => $query
            ->where('intro_event_records.nis_status', NisStatus::NIS)
            ->orWhereNull('intro_event_records.nis_status'));
    }

    /**
     * Whether occurrences still point at this event. Their foreign key
     * restricts deletion, so a permanent delete would fail on the database;
     * the UI disables it instead. Reads a withCount('occurrences') value when
     * the query loaded one, so a page of rows costs no extra queries.
     */
    public function hasOccurrences(): bool
    {
        return ($this->occurrences_count ?? $this->occurrences()->count()) > 0;
    }
}
