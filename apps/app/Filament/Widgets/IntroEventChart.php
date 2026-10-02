<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\IntroEventRecord;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Js;

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

    /** Filament polls widgets every 5s by default; this data only changes on import or edit. */
    protected ?string $pollingInterval = null;

    /**
     * Live introduction events in the validated baseline only — the soft-delete
     * scope rides on the model, so charts over child tables constrain through
     * this rather than joining raw.
     *
     * @return Builder<IntroEventRecord>
     */
    protected function liveEvents(): Builder
    {
        return IntroEventRecord::query()->baseline();
    }

    /**
     * The download button, top-right in the 32px+ strip every chart keeps
     * above its grid. Vertical on purpose, though it holds one icon: a
     * horizontal toolbox pushes a hover label that would overflow the right
     * edge *below* the icon, onto the plot, overriding textPosition. Vertical
     * and right-aligned, ECharts puts it to the icon's left, inside the strip.
     * The click handler is a JS function, so it arrives through
     * extraJsOptions().
     *
     * @return array<string, mixed>
     */
    protected function toolbox(): array
    {
        return [
            'orient' => 'vertical',
            'right' => 0,
            'top' => 0,
            'itemSize' => 14,
            'feature' => [
                'myDownload' => [
                    'title' => 'Download PNG',
                    'icon' => 'path://M4.7,22.9L29.3,45.5L54.7,23.4M4.6,43.6L4.6,58L53.8,58L53.8,43.6M29.2,45.1L29.2,0',
                ],
            ],
            'iconStyle' => ['borderColor' => self::GRAY_500],
            'emphasis' => ['iconStyle' => [
                'borderColor' => self::TEAL_600,
                'textFill' => self::GRAY_600,
            ]],
        ];
    }

    /**
     * A line printed under the title in the exported PNG, for charts whose
     * on-screen note would otherwise be lost.
     */
    protected function exportNote(): ?string
    {
        return null;
    }

    /**
     * Wires the download button: ECharts renders the chart itself through
     * getDataURL (toolbox left out), and mamiasExportPng (resources/js/app.js)
     * puts it under the widget title and note.
     */
    protected function extraJsOptions(): ?RawJs
    {
        $graphic = Js::from([
            'title' => (string) $this->getHeading(),
            'note' => $this->exportNote(),
            'file' => str((string) $this->getHeading())->slug()->toString(),
        ]);

        return RawJs::make(<<<JS
            { toolbox: { feature: { myDownload: { onclick: (ecModel, api) => window.mamiasExportPng({
                ...{$graphic},
                src: api.getDataURL({ type: 'png', pixelRatio: 2, backgroundColor: '#ffffff', excludeComponents: ['toolbox'] }),
            }) } } } }
            JS);
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
