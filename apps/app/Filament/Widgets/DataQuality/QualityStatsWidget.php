<?php

declare(strict_types=1);

namespace App\Filament\Widgets\DataQuality;

use App\Services\DataQualityService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QualityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Data Quality Overview';

    protected function getStats(): array
    {
        $service = app(DataQualityService::class);

        return [
            $this->createRecordsStat($service),
            $this->createAttentionStat($service),
            $this->createStaleStat($service),
            $this->createDuplicateStat($service),
        ];
    }

    protected function createRecordsStat(DataQualityService $service): Stat
    {
        return Stat::make('Total Records', $service->getTotalRecordsCount())
            ->description('Across all entities')
            ->descriptionIcon('tabler-database')
            ->color('primary');
    }

    protected function createAttentionStat(DataQualityService $service): Stat
    {
        return Stat::make('Needs Attention', $service->getNeedsAttentionCount())
            ->description('Pending moderation, needs review, or non-accepted')
            ->descriptionIcon('tabler-alert-triangle')
            ->color('warning');
    }

    protected function createStaleStat(DataQualityService $service): Stat
    {
        return Stat::make('Stale WoRMS Sync', $service->getStaleWormsCount())
            ->description('Taxons with no or outdated WoRMS data (>90 days)')
            ->descriptionIcon('tabler-cloud-off')
            ->color('danger');
    }

    protected function createDuplicateStat(DataQualityService $service): Stat
    {
        return Stat::make('Potential Duplicates', $service->getDuplicateCount())
            ->description('Duplicate scientific names or taxon assignments')
            ->descriptionIcon('tabler-copy')
            ->color('info');
    }
}
