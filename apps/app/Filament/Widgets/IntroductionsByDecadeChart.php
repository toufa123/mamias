<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Support\RawJs;

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

    /** Delay between one decade's bar and the next as they rise, in ms. */
    private const BAR_STAGGER_MS = 60;

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
            'animationDuration' => 1200,
            'animationEasing' => 'cubicOut',
            'toolbox' => $this->toolbox(),
            'tooltip' => $this->tooltip(),
            'legend' => ['top' => 0, 'textStyle' => ['color' => self::GRAY_600]],
            'grid' => ['left' => '3%', 'right' => '3%', 'top' => 60, 'bottom' => '3%', 'containLabel' => true],
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
                    // Drawn left to right over the same span as the staggered bars.
                    'animationDuration' => 1200 + count($counts) * self::BAR_STAGGER_MS,
                    'animationEasing' => 'linear',
                    'symbol' => 'none',
                    'lineStyle' => ['color' => self::TEAL_500, 'width' => 2],
                    'itemStyle' => ['color' => self::TEAL_500],
                ],
            ],
        ];
    }

    /**
     * The bars' stagger is a per-bar function, which cannot travel through
     * getOptions() as JSON; added to the base's download wiring here.
     */
    protected function extraJsOptions(): ?RawJs
    {
        $stagger = self::BAR_STAGGER_MS;

        return RawJs::make('{ ...'.parent::extraJsOptions().", series: [{ animationDelay: (index) => index * {$stagger} }] }");
    }
}
