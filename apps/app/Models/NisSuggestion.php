<?php

namespace App\Models;

use App\Casts\CoordinatesCast;
use App\Enums\AcforScale;
use App\Enums\LiteratureStatus;
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

/**
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
    use HasFactory, SoftDeletes, Userstamps;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function literatures(): BelongsToMany
    {
        return $this->belongsToMany(Literature::class, 'nis_suggestion_literature')->withTimestamps();
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    public function resubmittedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resubmitted_from_id');
    }

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
        });
    }
}
