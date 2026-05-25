<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
        $totalSpecies = Taxon::count();
        $checkedAccepted = Taxon::where('catalogue_status', Catalogue_Status::checked_accepted->value)->count();
        $checkedNotAccepted = Taxon::where('catalogue_status', Catalogue_Status::checked_not_accepted->value)->count();
        $notChecked = Taxon::where(function ($query) {
            $query
                ->whereNull('catalogue_status')
                ->orWhere('catalogue_status', Catalogue_Status::not_checked->value)
                ->orWhere('catalogue_status', Catalogue_Status::no_data_from_worms->value);
        })->count();

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
            ->chart($this->generateChartData($stats['total']))
            ->color('primary');
    }

    protected function createAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Accepted', $stats['accepted'])
            ->description("{$stats['accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-check')
            ->chart($this->generateChartData($stats['accepted']))
            ->color('success');
    }

    protected function createNotAcceptedStat(array $stats): Stat
    {
        return Stat::make('Checked & Not Accepted', $stats['not_accepted'])
            ->description("{$stats['not_accepted_percentage']}% of total")
            ->descriptionIcon('tabler-circle-x')
            ->chart($this->generateChartData($stats['not_accepted']))
            ->color('danger');
    }

    protected function createNotCheckedStat(array $stats): Stat
    {
        return Stat::make('Not Checked Yet', $stats['not_checked'])
            ->description("{$stats['not_checked_percentage']}% of total")
            ->descriptionIcon('tabler-clock')
            ->chart($this->generateChartData($stats['not_checked']))
            ->color('warning');
    }

    protected function generateChartData(int $value): array
    {
        return [70, 50, 60, 80, 75, 90, max($value, 1)];
    }
}
