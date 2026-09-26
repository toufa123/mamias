<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\IntroEventRecord;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared look for the MAMIAS Data charts, taken from DESIGN-SYSTEM.md: flat
 * teal-600 bars, gray-200 hairline axes, gray-100 split lines, no radius, no
 * glow. The panel is light-only, so the values are the light ramp.
 */
abstract class IntroEventChart extends EChartWidget
{
    protected const TEAL_500 = '#078da0';

    protected const TEAL_600 = '#056273';

    protected const GRAY_100 = '#edf3f5';

    protected const GRAY_200 = '#d8e3e8';

    protected const GRAY_500 = '#5f7783';

    protected const GRAY_600 = '#47606b';

    protected const INK = '#0e2630';

    protected static bool $isCollapsible = true;

    protected static bool $isDiscovered = false;

    /**
     * Live introduction events only — the soft-delete scope rides on the model,
     * so charts over child tables constrain through this rather than joining raw.
     *
     * @return Builder<IntroEventRecord>
     */
    protected function liveEvents(): Builder
    {
        return IntroEventRecord::query();
    }

    /**
     * @return array<string, mixed>
     */
    protected function tooltip(string $trigger = 'axis'): array
    {
        return [
            'trigger' => $trigger,
            'axisPointer' => ['type' => 'shadow', 'shadowStyle' => ['color' => 'rgba(7, 141, 160, 0.08)']],
            'backgroundColor' => '#ffffff',
            'borderColor' => self::GRAY_200,
            'borderWidth' => 1,
            'textStyle' => ['color' => self::INK, 'fontSize' => 13],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function valueAxis(array $overrides = []): array
    {
        return array_replace_recursive([
            'type' => 'value',
            'minInterval' => 1,
            'axisLine' => ['show' => false],
            'axisTick' => ['show' => false],
            'axisLabel' => ['fontSize' => 11, 'color' => self::GRAY_500],
            'splitLine' => ['lineStyle' => ['color' => self::GRAY_100]],
        ], $overrides);
    }

    /**
     * @param  list<string>  $labels
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function categoryAxis(array $labels, array $overrides = []): array
    {
        return array_replace_recursive([
            'type' => 'category',
            'data' => $labels,
            'axisLine' => ['lineStyle' => ['color' => self::GRAY_200]],
            'axisTick' => ['show' => false],
            'axisLabel' => ['fontSize' => 12, 'color' => self::GRAY_600],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    protected function valueLabel(string $position = 'right'): array
    {
        return ['show' => true, 'position' => $position, 'fontSize' => 11, 'color' => self::TEAL_600];
    }
}
