<?php

namespace App\Models;

use App\Enums\Catalogue_Status;
use App\Enums\LiteratureStatus;
use App\Enums\Worms_Status;
use App\Services\TaxonNormalizer;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use WeakMap;

/**
 * Class Taxon
 *
 * Represents a taxonomic entity (species, genus, etc.) sourced from WoRMS
 * with catalogue validation status, synonym data, and environment classification.
 *
 * @property int $id
 * @property int|null $aphia_id
 * @property string|null $url
 * @property string $scientificname
 * @property string|null $authority
 * @property Worms_Status $worms_status
 * @property Catalogue_Status $catalogue_status
 * @property string|null $unacceptreason
 * @property string|null $rank
 * @property string|null $kingdom
 * @property string|null $phylum
 * @property string|null $class
 * @property string|null $order
 * @property string|null $family
 * @property string|null $genus
 * @property string|null $lsid
 * @property string|null $proposed_accepted_name
 * @property bool $is_extinct
 * @property array|null $environments
 * @property array|null $synonyms_data
 * @property string|null $Easin_id
 * @property Carbon|null $fetched_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $original_description_id
 * @property int|null $proposed_accepted_aphia_id
 * @property int|null $name_change_confidence How sure the proposed accepted name is this species, 0–99
 * @property array<int, array{label: string, points: int}>|null $name_change_reasons
 * @property string|null $dismissed_accepted_name A WoRMS accepted name the team chose not to follow
 * @property string|null $dismissed_reason
 * @property int|null $name_reviewer_id Scientist asked to decide on the proposed name
 *
 * @method HasMany introEvents()
 * @method HasMany nisSuggestions()
 * @method BelongsTo originalDescription()
 */
#[Fillable([
    'aphia_id',
    'url',
    'scientificname',
    'authority',
    'worms_status',
    'catalogue_status',
    'unacceptreason',
    'rank',
    'kingdom',
    'phylum',
    'class',
    'order',
    'family',
    'genus',
    'lsid',
    'proposed_accepted_name',
    'proposed_accepted_aphia_id',
    'name_change_confidence',
    'name_change_reasons',
    'dismissed_accepted_name',
    'dismissed_reason',
    'name_reviewer_id',
    'is_extinct',
    'environments',
    'synonyms_data',
    'Easin_id',
    'fetched_at',
    'notes',
    'original_description_id',
])]
#[Table('taxas')]
class Taxon extends Model implements Commentable
{
    use HasComments, HasFactory, LogsActivity, SoftDeletes, Userstamps;

    /**
     * Introduction events counted when a delete starts, read back once it has
     * succeeded. Keyed weakly by the model so nothing is written onto it.
     *
     * @var WeakMap<Taxon, int>|null
     */
    private static ?WeakMap $introEventCountsBeforeDelete = null;

    protected static function booted(): void
    {
        static::saving(function (Taxon $taxon): void {
            app(TaxonNormalizer::class)->normalize($taxon);
        });

        // Counted before the delete: intro_event_records.taxon_id cascades on
        // delete, so after a force delete there is nothing left to count. It
        // then includes trashed events, since the cascade takes those too.
        static::deleting(function (Taxon $taxon): void {
            self::$introEventCountsBeforeDelete ??= new WeakMap;
            self::$introEventCountsBeforeDelete[$taxon] = $taxon->isForceDeleting()
                ? $taxon->introEvents()->withTrashed()->count()
                : $taxon->introEvents()->count();
        });

        // Told only after the delete succeeded, so a delete the database
        // refuses never produces a message saying it happened. One place for
        // every delete button — table row, bulk, edit page, soft or force.
        static::deleted(function (Taxon $taxon): void {
            $count = self::$introEventCountsBeforeDelete[$taxon] ?? 0;
            unset(self::$introEventCountsBeforeDelete[$taxon]);

            if ($count === 0) {
                return;
            }

            $events = $count.' '.str('introduction event')->plural($count);

            if ($taxon->isForceDeleting()) {
                Notification::make()
                    ->title('Introduction events deleted')
                    ->body("{$taxon->scientificname} was permanently deleted, and its {$events} with it.")
                    ->danger()
                    ->persistent()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Species has introduction events')
                ->body("{$taxon->scientificname} is in the trash but still has {$events}. They stay in Intro Events, marked as linked to a deleted species. Restore the species to undo.")
                ->warning()
                ->persistent()
                ->send();
        });
    }

    /**
     * The taxon already occupying this scientific name, or null if it is free.
     *
     * Applies the same single normalization pass as the `saving` hook, so the
     * lookup compares against the value that would actually be stored — the
     * normalizer is not idempotent, and a second pass would test a name that
     * is never written. Soft-deleted taxa count: they still hold the unique index.
     */
    public static function findDuplicateOf(string $scientificname, ?int $exceptId = null): ?self
    {
        $scratch = new self;
        $scratch->scientificname = $scientificname;

        app(TaxonNormalizer::class)->normalize($scratch);

        return self::withTrashed()
            ->whereIn('scientificname', array_values(array_unique([$scientificname, $scratch->scientificname])))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->first();
    }

    protected function casts(): array
    {
        return [
            'aphia_id' => 'integer',
            'is_extinct' => 'boolean',
            'environments' => 'array',
            'worms_status' => Worms_Status::class,
            'catalogue_status' => Catalogue_Status::class,
            'synonyms_data' => 'array',
            'name_change_reasons' => 'array',
            'fetched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept(['synonyms_data', 'fetched_at']);
    }

    /**
     * The scientist asked to decide whether to follow the proposed accepted name.
     */
    public function nameReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'name_reviewer_id');
    }

    public function introEvents(): HasMany
    {
        return $this->hasMany(IntroEventRecord::class);
    }

    public function nisSuggestions(): HasMany
    {
        return $this->hasMany(NisSuggestion::class);
    }

    /**
     * The publication that first described this taxon, as listed by WoRMS.
     */
    public function originalDescription(): BelongsTo
    {
        return $this->belongsTo(Literature::class, 'original_description_id');
    }

    /**
     * Every approved reference for this taxon, oldest first, each with its role.
     * A reference reached several ways keeps the first role in this order:
     * original description, first record (intro events), supporting (approved
     * suggestions).
     *
     * Memoized per instance: the references tab reads it several times per render.
     *
     * @return Collection<int, array{literature: Literature, role: string}>
     */
    public function literatureReferences(): Collection
    {
        return once(fn () => $this->queryLiteratureReferences());
    }

    /**
     * @return Collection<int, array{literature: Literature, role: string}>
     */
    private function queryLiteratureReferences(): Collection
    {
        $groups = [
            'Original description' => Literature::query()->whereKey($this->original_description_id),
            'First record' => Literature::query()->whereHas('introEvents', fn ($q) => $q->where('taxon_id', $this->id)),
            'Supporting' => Literature::query()->whereHas('nisSuggestions', fn ($q) => $q
                ->where('taxon_id', $this->id)
                ->where('status', LiteratureStatus::APPROVED)),
        ];

        return collect($groups)
            ->flatMap(fn (Builder $query, string $role) => $query
                ->where('status', LiteratureStatus::APPROVED)
                ->get()
                ->map(fn (Literature $literature) => ['literature' => $literature, 'role' => $role]))
            ->unique(fn (array $row) => $row['literature']->id)
            ->sortBy(fn (array $row) => $row['literature']->year ?? PHP_INT_MAX)
            ->values();
    }
}
