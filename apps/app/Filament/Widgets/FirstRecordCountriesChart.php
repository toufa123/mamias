<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;

/**
 * The countries holding the most first Mediterranean records. first_country
 * is a JSON array stored as text, so an event shared by two countries counts
 * for both; rows that are not an array are skipped rather than failing the cast.
 */
class FirstRecordCountriesChart extends IntroEventChart
{
    protected static ?string $heading = 'Top countries of first record';

    protected static int $contentHeight = 360;

    protected int|string|array $columnSpan = 'full';

    private const LIMIT = 10;

    protected function getOptions(): array
    {
        $rows = DB::query()
            ->fromSub(
                $this->liveEvents()
                    ->toBase()
                    ->selectRaw('json_array_elements_text(first_country::json) as country')
                    ->where('first_country', 'like', '[%'),
                'countries',
            )
            ->select('country')
            ->selectRaw('count(*) as total')
            ->groupBy('country')
            ->orderByDesc('total')
            ->orderBy('country')
            ->limit(self::LIMIT)
            ->get()
            ->reverse()
            ->values();

        return [
            'tooltip' => $this->tooltip(),
            'grid' => ['left' => '3%', 'right' => '6%', 'top' => '3%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => $this->valueAxis(),
            'yAxis' => $this->categoryAxis($rows->pluck('country')->all()),
            'series' => [[
                'name' => 'First records',
                'type' => 'bar',
                'data' => $rows->pluck('total')->map(fn (mixed $total): int => (int) $total)->all(),
                'barWidth' => '60%',
                'itemStyle' => ['color' => self::TEAL_600, 'borderRadius' => 0],
                'label' => $this->valueLabel(),
            ]],
        ];
    }
}
