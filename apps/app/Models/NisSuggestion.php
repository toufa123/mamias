<?php

namespace App\Models;

use App\Casts\SafeCoordinateCast;
use App\Enums\LiteratureStatus;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Coordinate|null $location
 * @property float|null $depth
 * @property string|null $bibliography
 * @property string|null $doi
 * @property array|null $photo_paths
 * @property array|null $document_paths
 * @property LiteratureStatus $status
 * @property string|null $rejection_reason
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
    'bibliography',
    'doi',
    'photo_paths',
    'document_paths',
    'status',
    'rejection_reason',
])]
class NisSuggestion extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected function casts(): array
    {
        return [
            'aphia_id' => 'integer',
            'photo_paths' => 'array',
            'document_paths' => 'array',
            'status' => LiteratureStatus::class,
            'depth' => 'float',
            'location' => SafeCoordinateCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (NisSuggestion $suggestion): void {
            foreach (['suggested_scientific_name', 'authority', 'suggested_common_name', 'bibliography'] as $field) {
                if ($suggestion->$field !== null) {
                    $suggestion->$field = strip_tags($suggestion->$field);
                }
            }
        });
    }
}
