<?php

namespace App\Filament\Widgets;

use App\Models\Taxon;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Illuminate\Support\Collection;

class SpeciesByPhylumChart extends EChartWidget
{
    protected static ?string $heading = 'Number of Species by Phylum';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    protected static int $contentHeight = 400;

    protected int | string | array $columnSpan = 1;

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
                    'shadowStyle' => ['color' => 'rgba(0, 137, 157, 0.1)'],
                ],
                'backgroundColor' => 'rgba(255, 255, 255, 0.95)',
                'borderColor' => '#00899d',
                'borderWidth' => 1,
                'textStyle' => ['color' => '#333'],
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
                    'fontWeight' => 'bold',
                    'color' => '#374151',
                ],
                'axisLine' => [
                    'show' => true,
                    'lineStyle' => ['color' => '#e5e7eb', 'width' => 2],
                ],
                'axisTick' => ['show' => false],
                'axisLabel' => [
                    'fontSize' => 11,
                    'color' => '#6b7280',
                    'fontWeight' => '500',
                ],
                'splitLine' => [
                    'lineStyle' => ['color' => '#f3f4f6', 'type' => 'dashed'],
                ],
            ],
            'yAxis' => [
                'type' => 'category',
                'data' => $phyla,
                'axisTick' => ['alignWithLabel' => true, 'show' => false],
                'axisLine' => [
                    'lineStyle' => ['color' => '#e5e7eb', 'width' => 2],
                ],
                'axisLabel' => [
                    'interval' => 0,
                    'fontSize' => 12,
                    'fontWeight' => '500',
                    'color' => '#374151',
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
                                ['offset' => 0, 'color' => '#00899d'],
                                ['offset' => 1, 'color' => '#00d4e6'],
                            ],
                        ],
                        'borderRadius' => [0, 4, 4, 0],
                    ],
                    'label' => [
                        'show' => true,
                        'position' => 'right',
                        'fontSize' => 11,
                        'fontWeight' => 'bold',
                        'color' => '#00899d',
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
