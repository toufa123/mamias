<?php

namespace App\Models;

use App\Enums\NisStatus;
use App\Enums\Subregion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['intro_event_id', 'subregion', 'nis_status', 'first_arrival_year', 'notes'])]
class SubregionRecord extends Model
{
    use HasFactory, Userstamps;

    public function introEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'intro_event_id');
    }

    protected function casts(): array
    {
        return [
            'subregion' => Subregion::class,
            'nis_status' => NisStatus::class,
        ];
    }
}
