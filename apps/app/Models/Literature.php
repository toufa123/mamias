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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Class Literature
 *
 * Represents a bibliographic reference linked to species records in the catalogue.
 * Each reference has a unique auto-generated code (mamiasXXXXXX) and tracks
 * its review status through an observer.
 *
 * @property int $id
 * @property string $code
 * @property string|null $doi
 * @property LiteratureType|null $type
 * @property string $short_ref
 * @property string $full_ref
 * @property string|null $link
 * @property string|null $file_path
 * @property LiteratureStatus|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method HasMany introEvents()
 */
#[Fillable([
    'code',
    'doi',
    'type',
    'short_ref',
    'full_ref',
    'link',
    'file_path',
    'status',
])]
#[ObservedBy([LiteratureObserver::class])]
class Literature extends Model
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
     * Scope query to literature records created by a specific user.
     */
    public function scopeForUser($query, $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    /**
     * Save the literature record within a database transaction for atomic code generation.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        return DB::transaction(function () use ($options) {
            return parent::save($options);
        });
    }

    /**
     * Generate the next unique literature code in the format "mamiasXXXXXX".
     * Uses a database-level lock to prevent race conditions on sequential code generation.
     */
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
                $query->orderByRaw('CAST(SUBSTRING(code, ?) AS INTEGER) DESC', [$start]);
            } elseif ($driver === 'sqlite') {
                $query->orderByRaw('CAST(SUBSTR(code, ?) AS INTEGER) DESC', [$start]);
            } else {
                $query->orderByRaw('CAST(SUBSTRING(code, ?) AS UNSIGNED) DESC', [$start]);
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

    /**
     * Introduction event records referencing this literature.
     */
    public function introEvents(): HasMany
    {
        return $this->hasMany(IntroEventRecord::class);
    }
}
