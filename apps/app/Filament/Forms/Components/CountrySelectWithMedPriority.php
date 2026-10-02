<?php

namespace App\Filament\Forms\Components;

use Nakanakaii\Countries\Countries;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;

/**
 * Country select field that groups Mediterranean countries first in a
 * dedicated optgroup, then all remaining countries.
 *
 * Options are keyed by ISO code, which is what user profiles store. Fields
 * whose data holds country *names* instead (introduction events'
 * first_country, as written by the baseline importers) call storeNames():
 * the stored names are shown as codes and saved back as names.
 */
class CountrySelectWithMedPriority extends CountrySelect
{
    /** Where MAMIAS uses a different name than the package, for labels and stored names alike. */
    private const NAME_OVERRIDES = [
        'TR' => 'Türkiye',
        'SY' => 'Syria',
    ];

    /** Names stored for introduction events that differ from the label: only Gaza lies on the Mediterranean. */
    private const STORED_NAME_OVERRIDES = [
        'PS' => 'Gaza strip',
    ];

    /** Other spellings met in the data, lower-cased => ISO code. */
    private const ALIASES = [
        'turkey' => 'TR',
        'turkiye' => 'TR',
        'irael' => 'IL',
        'syrian arab republic' => 'SY',
        'palestine' => 'PS',
        'palestine, state of' => 'PS',
        'gaza' => 'PS',
    ];

    private const MEDITERRANEAN_CODES = [
        'AL', 'DZ', 'BA', 'HR', 'CY', 'EG', 'FR', 'GR', 'IL', 'IT',
        'LB', 'LY', 'MT', 'MC', 'ME', 'MA', 'SI', 'ES', 'SY', 'TN', 'TR',
    ];

    /** @var array<string, string>|null lower-cased name, code or alias => ISO code */
    private static ?array $codes = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->options(function () {
            $format = fn ($group) => $group->mapWithKeys(function ($country) {
                $name = self::NAME_OVERRIDES[$country['code']] ?? $country['name'];
                $label = $this->isDisplayingFlags
                    ? $this->renderFlag($country).' '.$name
                    : $name;

                return [$country['code'] => $label];
            })->all();

            $countries = collect(Countries::all())
                ->sortBy('name')
                ->partition(fn ($country) => in_array($country['code'], self::MEDITERRANEAN_CODES));

            return [
                'Mediterranean countries' => $format($countries[0]),
                'All other countries' => $format($countries[1]),
            ];
        });
    }

    /**
     * Keep the field's stored state as country names (a list of them when
     * multiple): names are shown as their codes and saved back as names. A
     * value no name or alias matches is kept as it is, so it surfaces in
     * validation rather than being dropped silently.
     */
    public function storeNames(): static
    {
        $this->afterStateHydrated(function (self $component, mixed $state): void {
            $toCode = fn (mixed $value): mixed => is_string($value) ? (self::codeFor($value) ?? $value) : $value;
            $component->state(is_array($state) ? array_values(array_map($toCode, $state)) : $toCode($state));
        });

        $this->dehydrateStateUsing(function (mixed $state): mixed {
            $toName = fn (mixed $value): mixed => is_string($value) ? (self::storedNameFor($value) ?? $value) : $value;

            return is_array($state) ? array_values(array_map($toName, $state)) : $toName($state);
        });

        return $this;
    }

    /**
     * The ISO code for a code, name (package or MAMIAS spelling) or known alias.
     */
    public static function codeFor(string $value): ?string
    {
        if (self::$codes === null) {
            self::$codes = self::ALIASES;

            foreach (Countries::all() as $country) {
                self::$codes[mb_strtolower($country['code'])] = $country['code'];
                self::$codes[mb_strtolower($country['name'])] = $country['code'];
            }

            foreach ([...self::NAME_OVERRIDES, ...self::STORED_NAME_OVERRIDES] as $code => $name) {
                self::$codes[mb_strtolower($name)] = $code;
            }
        }

        return self::$codes[mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)))] ?? null;
    }

    /**
     * The name stored for an introduction-event country, given its code.
     */
    public static function storedNameFor(string $code): ?string
    {
        $code = strtoupper($code);

        return self::STORED_NAME_OVERRIDES[$code]
            ?? self::NAME_OVERRIDES[$code]
            ?? collect(Countries::all())->firstWhere('code', $code)['name']
            ?? null;
    }

    /**
     * The stored names of the Mediterranean countries.
     *
     * @return list<string>
     */
    public static function mediterraneanNames(): array
    {
        return array_values(array_filter(array_map(self::storedNameFor(...), self::MEDITERRANEAN_CODES)));
    }

    /**
     * The stored name for any spelling of a country, or null when unrecognised.
     */
    public static function canonicalName(string $value): ?string
    {
        $code = self::codeFor($value);

        return $code === null ? null : self::storedNameFor($code);
    }
}
