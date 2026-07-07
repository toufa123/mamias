<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Models\IntroEventRecord;
use App\Models\Literature;
use App\Models\NisSuggestion;
use App\Models\Occurrence;
use App\Models\PathwayRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    private const CACHE_TTL = 3600;

    public function getTotalRecordsCount(): int
    {
        return Cache::remember('dq.total_records', self::CACHE_TTL, function (): int {
            return Taxon::count()
                + Occurrence::count()
                + NisSuggestion::count()
                + Literature::count()
                + IntroEventRecord::count()
                + SubregionRecord::count()
                + PathwayRecord::count();
        });
    }

    public function getNeedsAttentionCount(): int
    {
        return Cache::remember('dq.needs_attention', self::CACHE_TTL, function (): int {
            return Occurrence::where('status', 'pending')->count()
                + NisSuggestion::where('status', 'pending')->count()
                + IntroEventRecord::where('needs_review', true)->count()
                + Taxon::where('catalogue_status', '!=', Catalogue_Status::checked_accepted->value)
                    ->orWhereNull('catalogue_status')
                    ->count();
        });
    }

    public function getStaleWormsCount(): int
    {
        return Cache::remember('dq.stale_worms', self::CACHE_TTL, function (): int {
            return Taxon::whereNull('fetched_at')
                ->orWhere('fetched_at', '<', now()->subDays(90))
                ->count();
        });
    }

    public function getDuplicateCount(): int
    {
        return Cache::remember('dq.duplicates', self::CACHE_TTL * 6, function (): int {
            $taxonDupes = Taxon::select('scientificname')
                ->whereNotNull('scientificname')
                ->groupBy('scientificname')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            $ierDupes = IntroEventRecord::select('taxon_id')
                ->whereNotNull('taxon_id')
                ->groupBy('taxon_id')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            return $taxonDupes + $ierDupes;
        });
    }

    public function getRejectionRate(): array
    {
        return Cache::remember('dq.rejection_rate', self::CACHE_TTL, function (): array {
            return [
                'occurrence' => $this->calculateRejectionRate(new Occurrence),
                'nis_suggestion' => $this->calculateRejectionRate(new NisSuggestion),
            ];
        });
    }

    public function getCompletenessData(): array
    {
        return Cache::remember('dq.completeness', self::CACHE_TTL, function (): array {
            return [
                'Taxon' => $this->calculateCompleteness(Taxon::class, [
                    'scientificname', 'authority', 'rank', 'kingdom',
                    'phylum', 'class', 'order', 'family', 'genus',
                ]),
                'Occurrence' => $this->calculateCompleteness(Occurrence::class, [
                    'location', 'depth', 'acfor_scale', 'habitats',
                    'photo_paths', 'observed_at', 'intro_event_record_id',
                ]),
                'NisSuggestion' => $this->calculateCompleteness(NisSuggestion::class, [
                    'suggested_scientific_name', 'authority', 'aphia_id',
                    'location', 'depth', 'kingdom', 'acfor_scale', 'habitats',
                ]),
                'Literature' => $this->calculateCompleteness(Literature::class, [
                    'short_ref', 'type', 'full_ref', 'doi', 'code',
                ]),
                'IntroEventRecord' => $this->calculateCompleteness(IntroEventRecord::class, [
                    'taxon_id', 'first_introduction_year', 'first_country',
                    'nis_status', 'establishment_status',
                ]),
                'SubregionRecord' => $this->calculateCompleteness(SubregionRecord::class, [
                    'subregion', 'nis_status', 'first_arrival_year',
                ]),
                'PathwayRecord' => $this->calculateCompleteness(PathwayRecord::class, [
                    'category', 'subcategory', 'pathway_type', 'description',
                ]),
            ];
        });
    }

    public function getIssueDistribution(): array
    {
        return Cache::remember('dq.issue_distribution', self::CACHE_TTL, function (): array {
            $pendingOccurrences = Occurrence::where('status', 'pending')->count();
            $pendingSuggestions = NisSuggestion::where('status', 'pending')->count();
            $needsReview = IntroEventRecord::where('needs_review', true)->count();
            $staleWorms = $this->getStaleWormsCount();
            $nonAccepted = Taxon::where('catalogue_status', '!=', Catalogue_Status::checked_accepted->value)
                ->orWhereNull('catalogue_status')
                ->count();

            return [
                ['name' => 'Pending Moderation', 'value' => $pendingOccurrences + $pendingSuggestions],
                ['name' => 'Needs Review', 'value' => $needsReview],
                ['name' => 'Stale WoRMS Sync', 'value' => $staleWorms],
                ['name' => 'Non-Accepted Taxons', 'value' => $nonAccepted],
            ];
        });
    }

    public function getIssuesQuery(): Builder
    {
        $pendingOccurrences = DB::table('occurrences')
            ->select(
                DB::raw("'Occurrence' AS entity_type"),
                'id',
                DB::raw("'pending' AS status"),
                DB::raw("'pending_moderation' AS issue_type"),
                DB::raw("'medium' AS severity"),
                DB::raw("'Occurrence #' || id || ' is pending moderation' AS description"),
                'created_at'
            )
            ->where('status', 'pending');

        $pendingSuggestions = DB::table('nis_suggestions')
            ->select(
                DB::raw("'NisSuggestion' AS entity_type"),
                'id',
                'status',
                DB::raw("'pending_moderation' AS issue_type"),
                DB::raw("'medium' AS severity"),
                DB::raw("'NIS Suggestion #' || id || ' is pending' AS description"),
                'created_at'
            )
            ->where('status', 'pending');

        $needsReview = DB::table('intro_event_records')
            ->select(
                DB::raw("'IntroEventRecord' AS entity_type"),
                'id',
                DB::raw("'' AS status"),
                DB::raw("'needs_review' AS issue_type"),
                DB::raw("'high' AS severity"),
                DB::raw("'Intro Event Record #' || id || ' needs review' AS description"),
                'updated_at AS created_at'
            )
            ->where('needs_review', true);

        $staleWorms = DB::table('taxas')
            ->select(
                DB::raw("'Taxon' AS entity_type"),
                'id',
                DB::raw("'' AS status"),
                DB::raw("'stale_worms' AS issue_type"),
                DB::raw("'low' AS severity"),
                DB::raw("'Taxon #' || id || ' (' || COALESCE(scientificname, 'unnamed') || ') has stale WoRMS data' AS description"),
                DB::raw('COALESCE(fetched_at, created_at) AS created_at')
            )
            ->where(function (Builder $q) {
                $q->whereNull('fetched_at')
                    ->orWhere('fetched_at', '<', now()->subDays(90));
            });

        $nonAccepted = DB::table('taxas')
            ->select(
                DB::raw("'Taxon' AS entity_type"),
                'id',
                'catalogue_status AS status',
                DB::raw("'non_accepted' AS issue_type"),
                DB::raw("'high' AS severity"),
                DB::raw("'Taxon #' || id || ' (' || COALESCE(scientificname, 'unnamed') || ') is not accepted' AS description"),
                'updated_at AS created_at'
            )
            ->where(function (Builder $q) {
                $q->whereNull('catalogue_status')
                    ->orWhere('catalogue_status', '!=', Catalogue_Status::checked_accepted->value);
            });

        $first = $pendingOccurrences;
        $query = $first->unionAll($pendingSuggestions)
            ->unionAll($needsReview)
            ->unionAll($staleWorms)
            ->unionAll($nonAccepted);

        return $query->orderBy('created_at', 'desc');
    }

    public function clearCache(): void
    {
        Cache::forget('dq.total_records');
        Cache::forget('dq.needs_attention');
        Cache::forget('dq.stale_worms');
        Cache::forget('dq.duplicates');
        Cache::forget('dq.rejection_rate');
        Cache::forget('dq.completeness');
        Cache::forget('dq.issue_distribution');
    }

    private function calculateCompleteness(string $modelClass, array $fields): array
    {
        $total = $modelClass::count();

        if ($total === 0) {
            return [
                'total' => 0,
                'percentage' => 0,
                'fields' => collect($fields)->mapWithKeys(fn (string $f) => [$f => 0])->all(),
            ];
        }

        $filledCounts = [];
        $table = (new $modelClass)->getTable();

        foreach ($fields as $field) {
            $count = DB::table($table)->whereNotNull($field)->count();

            $filledCounts[$field] = (int) round(($count / $total) * 100);
        }

        $avgPercentage = count($fields) > 0
            ? (int) round(array_sum($filledCounts) / count($fields))
            : 0;

        return [
            'total' => $total,
            'percentage' => $avgPercentage,
            'fields' => $filledCounts,
        ];
    }

    private function calculateRejectionRate($model): array
    {
        $approved = $model->where('status', 'approved')->count();
        $rejected = $model->where('status', 'rejected')->count();
        $total = $approved + $rejected;

        return [
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $total,
            'rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0,
        ];
    }
}
