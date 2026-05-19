<?php

namespace App\Models;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Observers\LiteratureObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Class Literature
 *
 * @property int $id
 * @property string $code
 * @property string|null $doi
 * @property LiteratureType|null $type
 * @property string $short_ref
 * @property string $full_ref
 * @property string|null $link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LiteratureStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'code',
    'doi',
    'type',
    'status',
    'short_ref',
    'full_ref',
    'link',
    'file_path',
])]
#[ObservedBy([LiteratureObserver::class])]
class Literature extends Model
{
    use HasFactory, Userstamps;

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        return DB::transaction(function () use ($options) {
            return parent::save($options);
        });
    }

    final public static function generateNextCode(): string
    {
        try {
            $column = 'code';
            $start = 7;

            $lastRecord = self::where($column, 'like', 'mamias%')
                ->orderByRaw("CAST(SUBSTRING($column, $start) AS INTEGER) DESC")
                ->lockForUpdate()
                ->first();

            if (! $lastRecord || ! preg_match('/^mamias(\d{6})$/', $lastRecord->code, $matches)) {
                return 'mamias000001';
            }

            $lastNumber = (int) $matches[1];
            $nextNumber = $lastNumber + 1;

            return 'mamias'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            Log::error('Error generating next code: '.$e->getMessage());

            return 'mamias'.str_pad('1', 6, '0', STR_PAD_LEFT);
        }
    }

    final protected function casts(): array
    {
        return [
            'type' => LiteratureType::class,
            'status' => LiteratureStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', LiteratureStatus::APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LiteratureStatus::PENDING);
    }
}
