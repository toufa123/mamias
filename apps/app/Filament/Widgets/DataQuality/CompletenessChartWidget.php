<?php

declare(strict_types=1);

namespace App\Filament\Widgets\DataQuality;

use App\Services\DataQualityService;
use Elemind\FilamentECharts\Widgets\EChartWidget;

class CompletenessChartWidget extends EChartWidget
{
    protected static ?string $heading = 'Field Completeness by Entity';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    protected static int $contentHeight = 350;

    protected int|string|array $columnSpan = 1;

    protected function getOptions(): array
    {
        $service = app(DataQualityService::class);
        $data = $service->getCompletenessData();

        $categories = array_keys($data);
        $percentages = array_map(fn (array $d): int => $d['percentage'], $data);
        $totals = array_map(fn (array $d): int => $d['total'], $data);

        return [
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => ['type' => 'shadow'],
                'formatter' => function (array $params) use ($data): string {
                    $param = $params[0];
                    $entity = $param['name'];
                    $info = $data[$entity] ?? null;

                    if (! $info) {
                        return $param['name'].': '.$param['value'].'%';
                    }

                    $lines = ["<b>{$entity}</b>"];
                    $lines[] = "Overall: {$info['percentage']}% ({$info['total']} records)";
                    $lines[] = '<hr>';

                    foreach ($info['fields'] as $field => $pct) {
                        $color = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                        $lines[] = "<span style='color:{$color}'>●</span> {$field}: {$pct}%";
                    }

                    return implode('<br>', $lines);
                },
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => $categories,
                'axisLabel' => [
                    'rotate' => 30,
                    'fontSize' => 11,
                ],
            ],
            'yAxis' => [
                'type' => 'value',
                'max' => 100,
                'axisLabel' => [
                    'formatter' => '{value}%',
                ],
            ],
            'series' => [
                [
                    'type' => 'bar',
                    'data' => array_map(function (int $pct, string $entity) use ($totals): array {
                        $color = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');

                        return [
                            'value' => $pct,
                            'itemStyle' => ['color' => $color],
                            'name' => $entity,
                            'total' => $totals[$entity] ?? 0,
                        ];
                    }, $percentages, array_keys($data)),
                    'barWidth' => '50%',
                    'label' => [
                        'show' => true,
                        'position' => 'top',
                        'formatter' => '{c}%',
                        'fontSize' => 11,
                    ],
                ],
            ],
        ];
    }
}
