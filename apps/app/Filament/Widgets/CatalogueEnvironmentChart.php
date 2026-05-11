<?php

namespace App\Filament\Widgets;

use App\Enums\Environment;
use App\Models\Taxon;
use Elemind\FilamentECharts\Widgets\EChartWidget;

class CatalogueEnvironmentChart extends EChartWidget
{
    protected static ?string $heading = 'Distribution by Environment';

    protected static int $contentHeight = 350;

    protected static bool $isDiscovered = false;

    protected static bool $isCollapsible = true;

    protected int | string | array $columnSpan = 1;

    protected const ENV_COLORS = [
        'marine' => '#00899d',
        'freshwater' => '#10B981',
        'brackish' => '#F59E0B',
        'terrestrial' => '#64748B',
    ];

    protected function getOptions(): array
    {
        $data = [];

        foreach (Environment::cases() as $env) {
            $count = Taxon::whereJsonContains('environments', $env->value)->count();

            if ($count > 0) {
                $data[] = [
                    'value' => $count,
                    'name' => $env->getLabel(),
                    'itemStyle' => ['color' => self::ENV_COLORS[$env->value] ?? '#64748B'],
                ];
            }
        }

        return [
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b}: {c} ({d}%)',
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left',
            ],
            'series' => [
                [
                    'name' => 'Environment',
                    'type' => 'pie',
                    'radius' => ['40%', '70%'],
                    'center' => ['55%', '50%'],
                    'avoidLabelOverlap' => true,
                    'itemStyle' => [
                        'borderRadius' => 6,
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
                    'data' => $data,
                ],
            ],
        ];
    }
}
