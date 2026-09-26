<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

/**
 * New introductions per decade of first record, with the running total — the
 * trend chart of MSFD D2 / IMAP reporting. Empty decades are kept as zero so
 * the time axis stays linear.
 */
class IntroductionsByDecadeChart extends IntroEventChart
{
    protected static ?string $heading = 'Introductions by decade of first record';

    protected static int $contentHeight = 360;

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $byDecade = $this->liveEvents()
            ->toBase()
            ->selectRaw('(first_introduction_year / 10) * 10 as decade, count(*) as total')
            ->whereNotNull('first_introduction_year')
            ->groupBy('decade')
            ->pluck('total', 'decade')
            ->map(fn (mixed $total): int => (int) $total);

        if ($byDecade->isEmpty()) {
            return [];
        }

        $latestYear = (int) $this->liveEvents()->max('first_introduction_year');
        $labels = [];
        $counts = [];
        $cumulative = [];
        $runningTotal = 0;

        for ($decade = (int) $byDecade->keys()->min(); $decade <= (int) $byDecade->keys()->max(); $decade += 10) {
            $count = $byDecade->get($decade, 0);
            $runningTotal += $count;

            // The last decade is only complete once the data reaches its ninth year.
            $labels[] = $latestYear < $decade + 9 && $latestYear >= $decade
                ? "{$decade}s (to {$latestYear})"
                : "{$decade}s";
            $counts[] = $count;
            $cumulative[] = $runningTotal;
        }

        return [
            'tooltip' => $this->tooltip(),
            'legend' => ['top' => 0, 'textStyle' => ['color' => self::GRAY_600]],
            'grid' => ['left' => '3%', 'right' => '3%', 'top' => 40, 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => $this->categoryAxis($labels, ['axisLabel' => ['rotate' => 45, 'fontSize' => 11]]),
            'yAxis' => [
                $this->valueAxis(['name' => 'New introductions', 'nameTextStyle' => ['color' => self::GRAY_500]]),
                $this->valueAxis(['name' => 'Cumulative', 'nameTextStyle' => ['color' => self::GRAY_500], 'splitLine' => ['show' => false]]),
            ],
            'series' => [
                [
                    'name' => 'New introductions',
                    'type' => 'bar',
                    'data' => $counts,
                    'barWidth' => '60%',
                    'itemStyle' => ['color' => self::TEAL_600, 'borderRadius' => 0],
                ],
                [
                    'name' => 'Cumulative',
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => $cumulative,
                    'symbol' => 'none',
                    'lineStyle' => ['color' => self::TEAL_500, 'width' => 2],
                    'itemStyle' => ['color' => self::TEAL_500],
                ],
            ],
        ];
    }
}
