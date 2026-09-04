<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\Occurrence;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
// use Filament\Widgets\StatsOverviewWidget\Stat;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus as Stat;

/**
 * Stats widget showing the total count of pending occurrences that need
 * moderation, with a link to the intro event records index.
 */
class PendingOccurrencesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $pendingCount = Occurrence::where('status', 'pending')->count();

        return [
            Stat::make('Pending Occurrences', $pendingCount)
                ->description('Awaiting moderation review')
                ->descriptionIcon('tabler-clock')
                ->color('warning')
                ->url(IntroEventRecordResource::getUrl('index')),
        ];
    }
}
