<?php

namespace App\Filament\Widgets;

use App\Models\Taxon;
use Elemind\FilamentECharts\Widgets\EChartWidget;

/**
 * ECharts stacked bar widget showing the distribution of phyla across
 * kingdoms in the catalogue.
 */
class PhylumByKingdomChart extends EChartWidget
{
    protected static ?string $heading = 'Phylum by Kingdom';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    protected static int $contentHeight = 400;

    protected int|string|array $columnSpan = 'full';

    protected const PHYLUM_COLORS = [
        '#078da0', '#10b981', '#f59e0b', '#F43F5E',
        '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16',
        '#ef4444', '#14b8a6', '#a855f7', '#f97316',
        '#6366f1', '#22c55e', '#e11d48', '#0ea5e9',
        '#d946ef', '#facc15', '#5f7783', '#fb923c',
    ];

    protected function getOptions(): array
    {
        $taxa = Taxon::query()
            ->whereNotNull('kingdom')
            ->whereNotNull('phylum')
            ->where('kingdom', '!=', '')
            ->where('phylum', '!=', '')
            ->selectRaw('kingdom, phylum, COUNT(*) as total')
            ->groupBy('kingdom', 'phylum')
            ->orderBy('kingdom')
            ->orderByDesc('total')
            ->get();

        $kingdoms = $taxa->pluck('kingdom')->unique()->values();
        $phyla = $taxa->pluck('phylum')->unique()->values();

        // Keyed once; the nested loop below would otherwise rescan the whole
        // result set for every kingdom/phylum pair.
        $totals = $taxa->keyBy(fn ($row): string => $row->kingdom.'|'.$row->phylum);

        $series = [];
        foreach ($phyla as $index => $phylum) {
            $data = [];

            foreach ($kingdoms as $kingdom) {
                $data[] = (int) ($totals->get($kingdom.'|'.$phylum)?->total ?? 0);
            }

            $series[] = [
                'name' => $phylum,
                'type' => 'bar',
                'stack' => 'total',
                'data' => $data,
                'itemStyle' => [
                    'color' => self::PHYLUM_COLORS[$index % count(self::PHYLUM_COLORS)],
                    'borderRadius' => 0,
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowColor' => 'rgba(0, 0, 0, 0.3)',
                    ],
                ],
                'label' => [
                    'show' => true,
                    'position' => 'inside',
                    'fontSize' => 10,
                    'fontWeight' => 500,
                    'formatter' => '{c}',
                ],
            ];
        }

        $dynamicHeight = max(400, $kingdoms->count() * 60);

        return [
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => ['type' => 'shadow'],
                'backgroundColor' => 'rgba(255, 255, 255, 0.95)',
                'borderColor' => '#078da0',
                'borderWidth' => 1,
                'textStyle' => ['color' => '#0e2630'],
            ],
            'legend' => [
                'type' => 'scroll',
                'top' => 0,
                'textStyle' => ['fontSize' => 11],
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'top' => '15%',
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'value',
                'name' => 'Species Count',
                'nameTextStyle' => [
                    'fontSize' => 12,
                    'fontWeight' => 500,
                    'color' => '#47606b',
                ],
                'axisLabel' => ['fontSize' => 11, 'color' => '#5f7783'],
                'splitLine' => [
                    'lineStyle' => ['color' => '#edf3f5', 'type' => 'dashed'],
                ],
            ],
            'yAxis' => [
                'type' => 'category',
                'data' => $kingdoms->toArray(),
                'axisTick' => ['alignWithLabel' => true],
                'axisLabel' => [
                    'interval' => 0,
                    'fontSize' => 12,
                    'fontWeight' => 500,
                    'color' => '#47606b',
                ],
                'axisLine' => [
                    'lineStyle' => ['color' => '#d8e3e8', 'width' => 2],
                ],
            ],
            'series' => $series,
            'animationDuration' => 1500,
            'animationEasing' => 'cubicOut',
        ];
    }
}
