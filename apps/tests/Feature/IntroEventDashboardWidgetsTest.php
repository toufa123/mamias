<?php

declare(strict_types=1);

use App\Enums\EstablishmentStatus;
use App\Enums\Subregion;
use App\Filament\Widgets\FirstRecordCountriesChart;
use App\Filament\Widgets\IntroductionsByDecadeChart;
use App\Filament\Widgets\SubregionEstablishmentChart;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;

use function Pest\Livewire\livewire;

it('keeps empty decades on the axis and marks the incomplete last one', function () {
    IntroEventRecord::factory()->create(['first_introduction_year' => 1985]);
    IntroEventRecord::factory()->count(2)->create(['first_introduction_year' => 2003]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2003])->delete();

    $options = livewire(IntroductionsByDecadeChart::class)->get('options');

    expect($options['xAxis']['data'])->toBe(['1980s', '1990s', '2000s (to 2003)'])
        ->and($options['series'][0]['data'])->toBe([1, 0, 2])
        ->and($options['series'][1]['data'])->toBe([1, 1, 3]);
});

it('stacks statuses that share a colour and sends the rest to other', function () {
    $event = IntroEventRecord::factory()->create();

    foreach ([EstablishmentStatus::Casual, EstablishmentStatus::Vagrant, EstablishmentStatus::Questionable, null] as $status) {
        SubregionRecord::factory()->create([
            'intro_event_id' => $event->id,
            'subregion' => Subregion::EMED,
            'establishment_status' => $status,
        ]);
    }

    $series = collect(livewire(SubregionEstablishmentChart::class)->get('options')['series'])
        ->mapWithKeys(fn (array $series): array => [$series['name'] => $series['data']]);
    $emed = array_search(Subregion::EMED, Subregion::cases(), true);

    expect($series->keys()->all())->toBe(['Casual / vagrant', 'Unknown / other'])
        ->and($series['Casual / vagrant'][$emed])->toBe(2)
        ->and($series['Unknown / other'][$emed])->toBe(2);
});

it('counts every country of a shared first record', function () {
    IntroEventRecord::factory()->create(['first_country' => ['Israel', 'Lebanon']]);
    IntroEventRecord::factory()->create(['first_country' => ['Israel']]);

    $options = livewire(FirstRecordCountriesChart::class)->get('options');

    expect($options['yAxis']['data'])->toBe(['Lebanon', 'Israel'])
        ->and($options['series'][0]['data'])->toBe([1, 2]);
});
