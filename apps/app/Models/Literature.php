<?php

namespace App\Models;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Observers\LiteratureObserver;
use App\Services\DoiMetadataService;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;
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
 * @property int|null $year
 * @property bool $is_retracted
 * @property string|null $suggested_doi
 * @property Carbon|null $crossref_checked_at
 * @property string|null $review_comment
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method HasMany introEvents()
 * @method BelongsToMany nisSuggestions()
 * @method BelongsTo reviewer()
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
    'year',
    'is_retracted',
    'suggested_doi',
    'crossref_checked_at',
    'review_comment',
    'reviewed_by',
    'reviewed_at',
])]
#[ObservedBy([LiteratureObserver::class])]
class Literature extends Model implements Commentable
{
    use HasComments, HasFactory, LogsActivity, Userstamps;

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

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $query = self::where($column, 'regexp', '^mamias[0-9]{6}$');
            } else {
                $query = self::where($column, 'like', 'mamias%');
            }

            /*
             * The offset is inlined rather than bound. As a placeholder it
             * arrives untyped, and PostgreSQL then resolves SUBSTRING(code, $1)
             * to the *regex* overload substring(text from pattern) instead of
             * substring(text from int). Every row cast to NULL, the ordering
             * became arbitrary, and this method kept returning first_row + 1 —
             * handing out a duplicate code as soon as a second record existed.
             * 7 is the fixed length of the "mamias" prefix plus one.
             */
            if ($driver === 'pgsql') {
                $query->orderByRaw('CAST(SUBSTRING(code FROM 7) AS INTEGER) DESC');
            } elseif ($driver === 'sqlite') {
                $query->orderByRaw('CAST(SUBSTR(code, 7) AS INTEGER) DESC');
            } else {
                $query->orderByRaw('CAST(SUBSTRING(code, 7) AS UNSIGNED) DESC');
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
            // Without this the attribute stays a raw string, so every
            // `$record->status === LiteratureStatus::PENDING` comparison — the
            // approve/reject visibility rules included — silently evaluates
            // false. The class docblock already declares it as the enum.
            'status' => LiteratureStatus::class,
            'year' => 'integer',
            'is_retracted' => 'boolean',
            'crossref_checked_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Every write path (panel form, user submissions, imports, the enrich
     * command) stores the bare lowercase DOI, so the unique index catches
     * the same DOI pasted as a URL or with a "doi:" prefix.
     */
    protected function doi(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => DoiMetadataService::normalize($value));
    }

    /**
     * BibTeX entry: Crossref's via DOI content negotiation, or a minimal
     *
     * @misc built from the stored reference when there is no DOI (or Crossref
     * does not answer).
     */
    public function toBibtex(): string
    {
        if ($this->doi && $entry = app(DoiMetadataService::class)->bibtex($this->doi)) {
            return $entry;
        }

        $fields = array_filter([
            'title' => $this->full_ref,
            'year' => $this->year,
            'url' => $this->link,
            'note' => $this->short_ref,
        ], fn ($value) => filled($value));

        $lines = collect($fields)
            ->map(fn ($value, string $key) => "  {$key} = {".str_replace(['{', '}'], '', (string) $value).'}')
            ->implode(",\n");

        return "@misc{{$this->code},\n{$lines}\n}";
    }

    /**
     * Introduction event records referencing this literature.
     */
    public function introEvents(): HasMany
    {
        return $this->hasMany(IntroEventRecord::class);
    }

    /**
     * Users who review submitted references.
     *
     * @return EloquentCollection<int, User>
     */
    public static function moderators(): EloquentCollection
    {
        // whereHas, not User::role(): that throws when a role has not been created yet.
        return User::whereHas('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'scientist']))->get();
    }

    /**
     * The moderator who approved or rejected the reference.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Species suggestions citing this literature.
     */
    public function nisSuggestions(): BelongsToMany
    {
        return $this->belongsToMany(NisSuggestion::class, 'nis_suggestion_literature')->withTimestamps();
    }

    /**
     * Taxa whose original description is this literature.
     */
    public function describedTaxa(): HasMany
    {
        return $this->hasMany(Taxon::class, 'original_description_id');
    }

    /**
     * Number of records citing this reference. Uses the `*_count` attributes
     * when the query loaded them with withCount(), and counts otherwise.
     */
    public function citationCount(): int
    {
        return ($this->intro_events_count ?? $this->introEvents()->count())
            + ($this->nis_suggestions_count ?? $this->nisSuggestions()->count())
            + ($this->described_taxa_count ?? $this->describedTaxa()->count());
    }

    /**
     * References whose full text is close to the given one (pg_trgm), most
     * similar first — the same paper typed with other punctuation or casing.
     */
    public function scopeSimilarTo(Builder $query, string $fullRef, float $threshold = 0.6): Builder
    {
        return $query
            ->whereRaw('similarity(full_ref, ?) >= ?', [$fullRef, $threshold])
            ->orderByRaw('similarity(full_ref, ?) DESC', [$fullRef]);
    }

    /**
     * Moves every citation of this reference onto $target, then deletes this
     * one. Deleting first would lose them: intro event records cascade.
     */
    public function mergeInto(self $target): void
    {
        DB::transaction(function () use ($target): void {
            $this->introEvents()->update(['literature_id' => $target->id]);
            $this->describedTaxa()->update(['original_description_id' => $target->id]);
            $target->nisSuggestions()->syncWithoutDetaching($this->nisSuggestions()->pluck('nis_suggestions.id'));

            activity()
                ->causedBy(auth()->user())
                ->performedOn($target)
                ->withProperties(['merged' => $this->only(['id', 'code', 'short_ref', 'doi'])])
                ->event('merged')
                ->log('merged');

            $this->delete();
        });
    }

    /**
     * Constrains a comments query to the ones the other side has not answered:
     * the submitter's since a moderator last wrote ($fromSubmitter), or the
     * moderators' since the submitter last wrote. Commentions keeps no read
     * receipts, so replying is what clears it. Runs correlated to `literatures`.
     */
    public static function unansweredComments(bool $fromSubmitter): Closure
    {
        $bySubmitter = 'author_type = ? and author_id = literatures.created_by';
        $userType = (new User)->getMorphClass();

        return fn (Builder $query): Builder => $query
            ->whereRaw(($fromSubmitter ? '' : 'not ')."(comments.{$bySubmitter})", [$userType])
            ->whereRaw(
                'comments.created_at > coalesce((select max(c.created_at) from comments c where c.commentable_type = comments.commentable_type and c.commentable_id = comments.commentable_id and '.($fromSubmitter ? 'not ' : '')."(c.{$bySubmitter})), '-infinity')",
                [$userType],
            );
    }

    /**
     * References with discussion comments awaiting a reply (see unansweredComments()).
     */
    public function scopeWithUnansweredComments(Builder $query, bool $fromSubmitter): Builder
    {
        return $query->whereHas('comments', self::unansweredComments($fromSubmitter));
    }

    /**
     * Pending references, counted once per request: the list tab badge, the
     * default tab and the panel alert box all ask.
     */
    public static function pendingCount(): int
    {
        return once(fn (): int => self::where('status', LiteratureStatus::PENDING)->count());
    }
}
