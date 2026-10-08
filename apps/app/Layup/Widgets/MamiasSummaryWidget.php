<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use App\Enums\Subregion;
use App\Services\MediterraneanDashboard;
use Crumbls\Layup\View\BaseWidget;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;

/**
 * "MAMIAS at a glance" for Layup pages (the home page): the Mediterranean
 * dashboard's headline figures, NIS per EcAp sub-region, establishment, top
 * phyla and the latest arrivals, each linking on to the dashboard, the map or
 * the species. Figures come from MediterraneanDashboard at render time, the
 * validated baseline, so they always match the dashboard.
 *
 * ponytail: computed per request, like the charts (a handful of grouped
 * queries over ~1,000 events); cache when the home page traffic asks for it.
 */
class MamiasSummaryWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'mamias-summary';
    }

    public static function getLabel(): string
    {
        return 'MAMIAS at a glance';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-presentation-chart-bar';
    }

    /**
     * @return list<TextInput>
     */
    public static function getContentFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->placeholder('MAMIAS at a glance'),
        ];
    }

    public static function getDefaultData(): array
    {
        return ['title' => null];
    }

    public static function getPreview(array $data): string
    {
        return 'MAMIAS at a glance';
    }

    public function render(): View
    {
        $dashboard = app(MediterraneanDashboard::class)->forCountry(null);

        $subregions = collect($dashboard->nisBySubregion())
            ->sortDesc()
            ->map(fn (int $count, string $code): array => [
                'code' => $code,
                'label' => Subregion::from($code)->getLabel(),
                'count' => $count,
            ])
            ->values()
            ->all();

        $phyla = collect($dashboard->taxonomy())
            ->flatMap(fn (array $kingdom): array => $kingdom['children'])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        return view('layup.widgets.mamias-summary', [
            'data' => $this->data,
            'title' => filled($this->data['title'] ?? null) ? $this->data['title'] : 'MAMIAS at a glance',
            'headline' => $dashboard->headline(),
            'subregions' => $subregions,
            'establishment' => $dashboard->establishment(),
            'phyla' => $phyla,
            'arrivals' => $dashboard->latestArrivals(),
        ]);
    }
}
