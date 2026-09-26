<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CbdPathwayCategory;
use App\Models\PathwayRecord;

/**
 * Introduction events per CBD pathway category. An event with several
 * pathways counts once in each, so the bars can sum past the event total.
 */
class IntroductionPathwaysChart extends IntroEventChart
{
    protected static ?string $heading = 'Introduction pathways (CBD)';

    protected static ?string $subheading = 'An event with several pathways counts in each';

    protected static int $contentHeight = 360;

    protected function getOptions(): array
    {
        $counts = PathwayRecord::query()
            ->whereHas('introEvent')
            ->toBase()
            ->select('category')
            ->selectRaw('count(distinct intro_event_id) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // Ascending, so the largest bar sits at the top of a horizontal chart.
        $rows = collect(CbdPathwayCategory::cases())
            ->map(fn (CbdPathwayCategory $category): array => [$category->getLabel(), (int) $counts->get($category->value, 0)])
            ->sortBy(1)
            ->values();

        return [
            'tooltip' => $this->tooltip(),
            'grid' => ['left' => '3%', 'right' => '8%', 'top' => '3%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => $this->valueAxis(),
            'yAxis' => $this->categoryAxis($rows->pluck(0)->all()),
            'series' => [[
                'name' => 'Introduction events',
                'type' => 'bar',
                'data' => $rows->pluck(1)->all(),
                'barWidth' => '60%',
                'itemStyle' => ['color' => self::TEAL_600, 'borderRadius' => 0],
                'label' => $this->valueLabel(),
            ]],
        ];
    }
}
