<?php

namespace App\Models;

use App\Enums\LiteratureType;
use App\Observers\LiteratureObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'code',
    'doi',
    'type',
    'short_ref',
    'full_ref',
    'link',
    'created_at',
    'updated_at',
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
            $driver = DB::getDriverName();
            $column = 'code';
            $start = 7;

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $query = self::where($column, 'regexp', '^mamias[0-9]{6}$');
            } else {
                $query = self::where($column, 'like', 'mamias%');
            }

            if ($driver === 'pgsql') {
                $query->orderByRaw("CAST(SUBSTRING($column, $start) AS INTEGER) DESC");
            } elseif ($driver === 'sqlite') {
                $query->orderByRaw("CAST(SUBSTR($column, $start) AS INTEGER) DESC");
            } else {
                $query->orderByRaw("CAST(SUBSTRING($column, $start) AS UNSIGNED) DESC");
            }

            $lastRecord = $query->lockForUpdate()->first();

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // public function introEvents(): BelongsToMany
    // {
    //     return $this->belongsToMany(IntroEvent::class, 'intro_event_literature');
    // }
}
