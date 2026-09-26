<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EstablishmentStatus;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\IntroEventRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus as Stat;

/**
 * Headline numbers for the introduction events. The two review counts link to
 * the list page tab that holds exactly those records.
 */
class IntroEventStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'MAMIAS Data';

    protected function getStats(): array
    {
        $counts = IntroEventRecord::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where establishment_status = ?) as established', [EstablishmentStatus::Established->value])
            ->selectRaw('count(*) filter (where needs_review) as needs_review')
            ->selectRaw('count(*) filter (where pathway_check is not null) as pathway_check')
            ->first();

        $total = (int) $counts->total;
        $established = (int) $counts->established;
        $share = $total > 0 ? round($established / $total * 100) : 0;

        return [
            Stat::make('Introduction events', number_format($total))
                ->description('Species × first Mediterranean record')
                ->descriptionIcon('tabler-fish')
                ->icon('tabler-fish')
                ->color('primary')
                ->url(IntroEventRecordResource::getUrl('index')),
            Stat::make('Established', number_format($established))
                ->description("{$share}% of events, basin level")
                ->descriptionIcon('tabler-circle-check')
                ->icon('tabler-circle-check')
                ->color('established'),
            Stat::make('Needs review', number_format((int) $counts->needs_review))
                ->description('Values the importer could not read')
                ->descriptionIcon('tabler-eye-search')
                ->icon('tabler-eye-search')
                ->color('gray')
                ->url(IntroEventRecordResource::getUrl('index', ['tab' => 'needs_review'])),
            Stat::make('Pathway check', number_format((int) $counts->pathway_check))
                ->description('Pathway differs from EASIN')
                ->descriptionIcon('tabler-route')
                ->icon('tabler-route')
                ->color('gray')
                ->url(IntroEventRecordResource::getUrl('index', ['tab' => 'pathway_check'])),
        ];
    }
}
