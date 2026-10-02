<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Subregion;
use App\Services\MediterraneanDashboard;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Vite;

/**
 * NIS per EcAp sub-region as a jsvectormap choropleth; the map itself is
 * built in resources/js/subregion-map.js. Counts the introduction events
 * reported in each sub-region, via MediterraneanDashboard.
 */
class SubregionNisMap extends Widget
{
    protected string $view = 'filament.widgets.subregion-nis-map';

    protected static bool $isDiscovered = false;

    /**
     * @return array{values: array<string, int>, labels: array<string, string>, script: string}
     */
    protected function getViewData(): array
    {
        return [
            'values' => $this->getNisCounts(),
            'labels' => collect(Subregion::cases())->mapWithKeys(fn (Subregion $subregion): array => [$subregion->value => $subregion->getLabel()])->all(),
            'script' => Vite::asset('resources/js/subregion-map.js'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getNisCounts(): array
    {
        return app(MediterraneanDashboard::class)->nisBySubregion();
    }
}
