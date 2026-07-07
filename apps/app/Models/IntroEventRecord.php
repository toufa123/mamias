<?php

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    public function literature(): BelongsTo
    {
        return $this->belongsTo(Literature::class);
    }

    public function subregionRecords(): HasMany
    {
        return $this->hasMany(SubregionRecord::class, 'intro_event_id');
    }

    public function pathwayRecords(): HasMany
    {
        return $this->hasMany(PathwayRecord::class, 'intro_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class, 'intro_event_record_id');
    }
}
