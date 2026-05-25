<?php

namespace App\Filament\Widgets;

use App\Models\Taxon;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Illuminate\Support\Collection;

class SpeciesByKingdomChart extends EChartWidget
{
    protected static ?string $heading = 'Number of Species by Kingdom';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    protected static int $contentHeight = 400;

    protected int | string | array $columnSpan = 1;

    protected const COLOR_PALETTE = [
        '#00899d',
        '#10b981',
        '#f59e0b',
        '#F43F5E',
        '#8b5cf6',
        '#ec4899',
        '#06b6d4',
        '#84cc16',
    ];

    protected function getOptions(): array
    {
        $data = $this->getKingdomData();
        $seriesData = $this->prepareSeriesData($data);

        return [
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b}: {c} species ({d}%)',
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left',
            ],
            'series' => [
                [
                    'name' => 'Species by Kingdom',
                    'type' => 'pie',
                    'radius' => [20, 140],
                    'center' => ['50%', '55%'],
                    'roseType' => 'area',
                    'itemStyle' => [
                        'borderRadius' => 8,
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'show' => true,
                        'position' => 'outside',
                        'formatter' => '{b}: {c}',
                        'fontSize' => 12,
                    ],
                    'labelLine' => [
                        'show' => true,
                        'length' => 10,
                        'length2' => 20,
                    ],
                    'emphasis' => [
                        'label' => [
                            'show' => true,
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                        ],
                        'itemStyle' => [
                            'shadowBlur' => 10,
                            'shadowOffsetX' => 0,
                            'shadowColor' => 'rgba(0, 0, 0, 0.5)',
                        ],
                    ],
                    'data' => $seriesData,
                ],
            ],
        ];
    }

    protected function getKingdomData(): Collection
    {
        return Taxon::selectRaw('kingdom, COUNT(*) as count')
            ->whereNotNull('kingdom')
            ->where('kingdom', '!=', '')
            ->groupBy('kingdom')
            ->orderByDesc('count')
            ->get();
    }

    protected function prepareSeriesData(Collection $data): array
    {
        $seriesData = [];

        foreach ($data as $index => $item) {
            $seriesData[] = [
                'value' => $item->count,
                'name' => $item->kingdom,
                'itemStyle' => [
                    'color' => self::COLOR_PALETTE[$index % count(self::COLOR_PALETTE)],
                ],
            ];
        }

        return $seriesData;
    }
}
