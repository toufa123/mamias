<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\OccurrenceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable([
    'user_id',
    'intro_event_record_id',
    'location',
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
    use HasFactory;

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
