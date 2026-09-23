<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
// use Filament\Widgets\StatsOverviewWidget\Stat;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus as Stat;

/**
 * Stats overview widget showing catalogue totals: total species, accepted,
 * not accepted, and not yet checked counts with percentage breakdowns.
 */
class CatalogueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'MAMIAS Catalogue';

    protected function getStats(): array
    {
        $stats = $this->getCatalogueStatistics();

        return [
            $this->createTotalSpeciesStat($stats),
            $this->createAcceptedStat($stats),
            $this->createNotAcceptedStat($stats),
            $this->createNotCheckedStat($stats),
        ];
    }

    protected function getCatalogueStatistics(): array
    {
        // One grouped pass over `taxas` instead of four separate COUNT scans.
        // The buckets are resolved in PHP so the query stays plain `GROUP BY`
        // rather than a vendor-specific conditional aggregate.
        // toBase() keeps the model's global scopes (soft deletes included) while
        // returning raw rows, so the enum cast never runs over a stored value
        // that no longer maps to a case.
        $counts = Taxon::query()
            ->toBase()
            ->select('catalogue_status')
            ->selectRaw('count(*) as total')
            ->groupBy('catalogue_status')
            ->pluck('total', 'catalogue_status');

        // A null catalogue_status arrives keyed as '', which is what get(null) looks up.
        $bucket = fn (?string ...$statuses): int => array_sum(
            array_map(fn (?string $status): int => (int) $counts->get($status, 0), $statuses)
        );

        $totalSpecies = (int) $counts->sum();
        $checkedAccepted = $bucket(Catalogue_Status::checked_accepted->value);
        $checkedNotAccepted = $bucket(Catalogue_Status::checked_not_accepted->value);
        $notChecked = $bucket(
            null,
            Catalogue_Status::not_checked->value,
            Catalogue_Status::no_data_from_worms->value,
        );

        return [
            'total' => $totalSpecies,
            'accepted' => $checkedAccepted,
            'not_accepted' => $checkedNotAccepted,
            'not_checked' => $notChecked,
            'accepted_percentage' => $this->calculatePercentage($checkedAccepted, $totalSpecies),
            'not_accepted_percentage' => $this->calculatePercentage($checkedNotAccepted, $totalSpecies),
            'not_checked_percentage' => $this->calculatePercentage($notChecked, $totalSpecies),
        ];
    }

    protected function calculatePercentage(int|float $part, int|float $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    protected function createTotalSpeciesStat(array $stats): Stat
    {
        return Stat::make('Total Species in Catalogue', $stats['total'])
            ->description('All species records')
            ->descriptionIcon('tabler-list')
            ->icon('tabler-list')
            ->chart($this->generateChartData($stats['total']))
            ->color('primary');
    }

    protected function createAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Accepted', $stats['accepted'])
            ->description("{$stats['accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-check')
            ->icon('tabler-circle-check')
            ->chart($this->generateChartData($stats['accepted']))
            ->color('success');
    }

    protected function createNotAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Not Accepted', $stats['not_accepted'])
            ->description("{$stats['not_accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-x')
            ->icon('tabler-circle-x')
            ->chart($this->generateChartData($stats['not_accepted']))
            ->color('danger');
    }

    protected function createNotCheckedStat(array $stats): Stat
    {
        return Stat::make('Not Checked Yet', $stats['not_checked'])
            ->description("{$stats['not_checked_percentage']}% of total")
            ->descriptionIcon('tabler-clock')
            ->icon('tabler-clock')
            ->chart($this->generateChartData($stats['not_checked']))
            ->color('warning');
    }

    protected function generateChartData(int $value): array
    {
        return [70, 50, 60, 80, 75, 90, max($value, 1)];
    }
}
