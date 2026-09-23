<?php

namespace App\Filament\Widgets;

use App\Models\Taxon;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Illuminate\Support\Collection;

/**
 * ECharts horizontal bar widget displaying the number of species grouped
 * by phylum (top 15 phyla).
 */
class SpeciesByPhylumChart extends EChartWidget
{
    protected static ?string $heading = 'Number of Species by Phylum';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    protected static int $contentHeight = 400;

    protected int|string|array $columnSpan = 1;

    protected const MAX_PHYLA_DISPLAY = 15;

    protected function getOptions(): array
    {
        $data = $this->getPhylumData();
        $phyla = $data->pluck('phylum')->toArray();
        $counts = $data->pluck('count')->toArray();

        return [
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'shadow',
                    'shadowStyle' => ['color' => 'rgba(7, 141, 160, 0.1)'],
                ],
                'backgroundColor' => 'rgba(255, 255, 255, 0.95)',
                'borderColor' => '#078da0',
                'borderWidth' => 1,
                'textStyle' => ['color' => '#0e2630'],
            ],
            'grid' => [
                'left' => '20%',
                'right' => '8%',
                'bottom' => '3%',
                'top' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'value',
                'name' => 'Number of Species',
                'nameLocation' => 'middle',
                'nameGap' => 35,
                'nameTextStyle' => [
                    'fontSize' => 13,
                    'fontWeight' => 500,
                    'color' => '#47606b',
                ],
                'axisLine' => [
                    'show' => true,
                    'lineStyle' => ['color' => '#d8e3e8', 'width' => 2],
                ],
                'axisTick' => ['show' => false],
                'axisLabel' => [
                    'fontSize' => 11,
                    'color' => '#5f7783',
                    'fontWeight' => '500',
                ],
                'splitLine' => [
                    'lineStyle' => ['color' => '#edf3f5', 'type' => 'dashed'],
                ],
            ],
            'yAxis' => [
                'type' => 'category',
                'data' => $phyla,
                'axisTick' => ['alignWithLabel' => true, 'show' => false],
                'axisLine' => [
                    'lineStyle' => ['color' => '#d8e3e8', 'width' => 2],
                ],
                'axisLabel' => [
                    'interval' => 0,
                    'fontSize' => 12,
                    'fontWeight' => '500',
                    'color' => '#47606b',
                    'margin' => 10,
                ],
            ],
            'series' => [
                [
                    'name' => 'Species Count',
                    'type' => 'bar',
                    'data' => $counts,
                    'barWidth' => '65%',
                    'itemStyle' => [
                        'color' => [
                            'type' => 'linear',
                            'x' => 0,
                            'y' => 0,
                            'x2' => 1,
                            'y2' => 0,
                            'colorStops' => [
                                ['offset' => 0, 'color' => '#078da0'],
                                ['offset' => 1, 'color' => '#6fc3d0'],
                            ],
                        ],
                        'borderRadius' => 0,
                    ],
                    'label' => [
                        'show' => true,
                        'position' => 'right',
                        'fontSize' => 11,
                        'fontWeight' => 500,
                        'color' => '#056273',
                        'formatter' => '{c}',
                    ],
                    'emphasis' => [
                        'itemStyle' => [
                            'shadowBlur' => 15,
                            'shadowOffsetX' => 0,
                            'shadowOffsetY' => 5,
                            'shadowColor' => 'rgba(0, 137, 157, 0.4)',
                        ],
                        'label' => ['show' => true, 'fontSize' => 13],
                    ],
                ],
            ],
            'animationDuration' => 1500,
            'animationEasing' => 'cubicOut',
        ];
    }

    protected function getPhylumData(): Collection
    {
        return Taxon::selectRaw('phylum, COUNT(*) as count')
            ->whereNotNull('phylum')
            ->where('phylum', '!=', '')
            ->groupBy('phylum')
            ->orderByDesc('count')
            ->limit(self::MAX_PHYLA_DISPLAY)
            ->get();
    }
}
