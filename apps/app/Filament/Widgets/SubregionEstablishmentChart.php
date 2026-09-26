<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EstablishmentStatus;
use App\Enums\Subregion;
use App\Models\SubregionRecord;

/**
 * Introduction events per subregion, stacked by establishment status in the
 * status vocabulary of DESIGN-SYSTEM.md. Statuses that share a colour there
 * share a stack here, so no colour means two things.
 */
class SubregionEstablishmentChart extends IntroEventChart
{
    protected static ?string $heading = 'Establishment status by EcAp sub-regions';

    protected static int $contentHeight = 360;

    private const OTHER = 'Unknown / other';

    /** Stack label => status text colour, in stacking order. */
    private const COLOURS = [
        'Invasive' => '#b42318',
        'Established' => '#b45309',
        'Casual / vagrant' => '#00558c',
        self::OTHER => self::GRAY_500,
    ];

    protected function getOptions(): array
    {
        $totals = [];

        $rows = SubregionRecord::query()
            ->whereHas('introEvent')
            ->toBase()
            ->select('subregion', 'establishment_status')
            ->selectRaw('count(*) as total')
            ->groupBy('subregion', 'establishment_status')
            ->get();

        foreach ($rows as $row) {
            $stack = self::stackFor(EstablishmentStatus::tryFrom((string) $row->establishment_status));
            $totals[$stack][$row->subregion] = ($totals[$stack][$row->subregion] ?? 0) + (int) $row->total;
        }

        $subregions = Subregion::cases();
        $series = [];

        foreach (self::COLOURS as $stack => $colour) {
            if (! isset($totals[$stack])) {
                continue;
            }

            $series[] = [
                'name' => $stack,
                'type' => 'bar',
                'stack' => 'status',
                'data' => array_map(fn (Subregion $subregion): int => $totals[$stack][$subregion->value] ?? 0, $subregions),
                'barWidth' => '55%',
                'itemStyle' => ['color' => $colour, 'borderRadius' => 0, 'borderColor' => '#ffffff', 'borderWidth' => 1],
            ];
        }

        return [
            'tooltip' => $this->tooltip(),
            'legend' => [
                'bottom' => 0,
                'icon' => 'rect',
                'itemWidth' => 12,
                'itemHeight' => 12,
                'itemGap' => 16,
                'textStyle' => ['color' => self::GRAY_600, 'fontSize' => 12],
            ],
            'grid' => ['left' => '3%', 'right' => '4%', 'top' => 16, 'bottom' => 40, 'containLabel' => true],
            // The codes: at half width the full names collide.
            'xAxis' => $this->categoryAxis(array_map(fn (Subregion $subregion): string => $subregion->value, $subregions)),
            'yAxis' => $this->valueAxis(),
            'series' => $series,
        ];
    }

    private static function stackFor(?EstablishmentStatus $status): string
    {
        return match ($status) {
            EstablishmentStatus::Invasive => 'Invasive',
            EstablishmentStatus::Established => 'Established',
            EstablishmentStatus::Casual, EstablishmentStatus::Vagrant => 'Casual / vagrant',
            default => self::OTHER,
        };
    }
}
