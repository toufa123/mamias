<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use Database\Factories\StagingIntroEventFactory;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One reviewed-but-not-yet-live intro event from the PAN Mediterranean import.
 *
 * A staged row holds values in three states at once, and the distinction
 * matters when reading this class:
 *
 *  - read straight from the file (year, country, NIS status),
 *  - proposed by the importer because the file has no such column
 *    (Med-wide establishment status, per-subregion status),
 *  - overridden by the admin during review.
 *
 * The column always holds the current best value; `proposals` records where it
 * came from, so the review screen can show "proposed Established, because
 * CMED and EMED both have arrival years" next to the field.
 *
 * @property-read Taxon|null $taxon
 */
class StagingIntroEvent extends Model
{
    /** @use HasFactory<StagingIntroEventFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PROMOTED = 'promoted';

    /**
     * The subregion columns, in the order the review screen shows them:
     * [subregion, establishment status, first arrival year, NIS status].
     *
     * Establishment status is blank on import — no sheet in the workbook
     * carries it. NIS status does arrive, merged from each subregion's own
     * sheet.
     *
     * @var array<int, array{0: Subregion, 1: string, 2: string, 3: string}>
     */
    public const SUBREGION_MAP = [
        [Subregion::WMED, 'wmed_establishment_status', 'wmed_first_arrival_year', 'wmed_nis_status'],
        [Subregion::CMED, 'cmed_establishment_status', 'cmed_first_arrival_year', 'cmed_nis_status'],
        [Subregion::ADRIA, 'adria_establishment_status', 'adria_first_arrival_year', 'adria_nis_status'],
        [Subregion::EMED, 'emed_establishment_status', 'emed_first_arrival_year', 'emed_nis_status'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'proposals' => 'array',
            'first_introduction_year' => 'integer',
            'nis_status' => NisStatus::class,
            'establishment_status' => EstablishmentStatus::class,
            'wmed_establishment_status' => EstablishmentStatus::class,
            'cmed_establishment_status' => EstablishmentStatus::class,
            'adria_establishment_status' => EstablishmentStatus::class,
            'emed_establishment_status' => EstablishmentStatus::class,
            'wmed_nis_status' => NisStatus::class,
            'cmed_nis_status' => NisStatus::class,
            'adria_nis_status' => NisStatus::class,
            'emed_nis_status' => NisStatus::class,
            'wmed_first_arrival_year' => 'integer',
            'cmed_first_arrival_year' => 'integer',
            'adria_first_arrival_year' => 'integer',
            'emed_first_arrival_year' => 'integer',
            'reviewed_at' => 'datetime',
            'promoted_at' => 'datetime',
        ];
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    /**
     * The import run that produced this row — how a whole batch is filtered
     * and, if it went wrong, discarded.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function promotedIntroEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'promoted_intro_event_id');
    }

    /**
     * Rows an admin has confirmed and that have not reached the live tables.
     *
     * @param  Builder<self>  $query
     */
    public function scopeReadyToPromote(Builder $query): void
    {
        $query->where('review_status', self::STATUS_CONFIRMED)
            ->whereNull('promoted_intro_event_id');
    }

    /**
     * A row cannot go live without a taxon: intro_event_records.taxon_id is the
     * one value that has no sensible default and no way to be proposed.
     */
    public function isPromotable(): bool
    {
        return $this->taxon_id !== null
            && $this->promoted_intro_event_id === null
            && $this->review_status === self::STATUS_CONFIRMED;
    }

    /**
     * What the importer proposed for a field, and why — null when the value
     * came straight from the file and needs no explanation.
     *
     * @return array{raw: string|null, proposed: string|null, reason: string}|null
     */
    public function proposalFor(string $field): ?array
    {
        return $this->proposals[$field] ?? null;
    }
}
