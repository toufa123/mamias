<?php

namespace App\Filament\Forms\Components;

use Nakanakaii\Countries\Countries;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;

class CountrySelectWithMedPriority extends CountrySelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->options(function () {
            $medCodes = [
                'AL', 'DZ', 'BA', 'HR', 'CY', 'EG', 'FR', 'GR', 'IL', 'IT',
                'LB', 'LY', 'MT', 'MC', 'ME', 'MA', 'SI', 'ES', 'SY', 'TN', 'TR',
            ];

            $overrides = [
                'TR' => 'Türkiye',
            ];

            $format = fn ($group) => $group->mapWithKeys(function ($country) use ($overrides) {
                $name = $overrides[$country['code']] ?? $country['name'];
                $label = $this->isDisplayingFlags
                    ? $this->renderFlag($country).' '.$name
                    : $name;

                return [$country['code'] => $label];
            })->all();

            $countries = collect(Countries::all())
                ->sortBy('name')
                ->partition(fn ($country) => in_array($country['code'], $medCodes));

            return [
                'Mediterranean countries' => $format($countries[0]),
                'All other countries' => $format($countries[1]),
            ];
        });
    }
}
