<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\Occurrence;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
