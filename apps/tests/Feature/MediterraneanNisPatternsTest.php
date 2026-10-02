<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Services\MediterraneanNisPatterns;

/**
 * An introduction event in the given sub-regions and pathways.
 *
 * @param  list<Subregion>  $subregions
 * @param  list<CbdPathwayCategory>  $pathways
 */
function patternEvent(array $subregions, array $pathways = [], ?int $year = 2000, string $phylum = 'Mollusca', string $class = 'Gastropoda'): IntroEventRecord
{
    $event = IntroEventRecord::factory()
        ->for(Taxon::factory()->state(['phylum' => $phylum, 'class' => $class]))
        ->create(['first_introduction_year' => $year]);

    foreach ($subregions as $subregion) {
        SubregionRecord::factory()->create(['intro_event_id' => $event->id, 'subregion' => $subregion]);
    }

    foreach ($pathways as $category) {
        PathwayRecord::factory()->create(['intro_event_id' => $event->id, 'category' => $category]);
    }

    return $event;
}

it('maps taxa to the paper\'s broad groups', function (string $phylum, string $class, string $group) {
    expect(MediterraneanNisPatterns::groupFor($phylum, $class))->toBe($group);
})->with([
    ['Rhodophyta', 'Florideophyceae', 'PHY'],
    ['Heterokontophyta', 'Phaeophyceae', 'PHY'],
    ['Chordata', 'Teleostei', 'FISH'],
    ['Chordata', 'Ascidiacea', 'ASC'],
    ['Chordata', 'Mammalia', 'MISC'],
    ['Foraminifera', 'Globothalamea', 'FOR'],
    ['Echinodermata', 'Asteroidea', 'MISC'],
]);

it('splits an event with several pathways equally, so shares sum to 100%', function () {
    patternEvent([Subregion::EMED], [CbdPathwayCategory::Corridor]);
    patternEvent([Subregion::EMED], [CbdPathwayCategory::Corridor, CbdPathwayCategory::TransportStowaway]);
    patternEvent([Subregion::WMED]);

    $basin = app(MediterraneanNisPatterns::class)->pathwayShares()['rows'][0];

    expect($basin['total'])->toBe(3)
        ->and($basin['shares']['COR'])->toBe(50.0)
        ->and($basin['shares']['TS'])->toBe(16.7)
        ->and($basin['shares']['UNK'])->toBe(33.3);
});

it('sorts events into unique and shared sub-region combinations, cumulatively by decade', function () {
    patternEvent([Subregion::EMED], year: 1975);
    patternEvent([Subregion::WMED, Subregion::CMED, Subregion::ADRIA, Subregion::EMED], year: 1995);
    patternEvent([Subregion::WMED, Subregion::EMED], year: 1995);
    patternEvent([Subregion::CMED], year: null);

    $shared = app(MediterraneanNisPatterns::class)->sharedBySubregion();
    $at = fn (int $decade): array => array_filter(array_combine($shared['combinations'], $shared['counts'][$decade]));

    expect($at(1980))->toBe(['EMED' => 1])
        ->and($at(2000))->toBe(['EMED' => 1, 'WMED+EMED' => 1, 'WMED+CMED+ADRIA+EMED' => 1]);
});

it('averages new NIS per year over 10-year cycles with the standard error', function () {
    foreach ([2011, 2011, 2020] as $year) {
        patternEvent([Subregion::EMED], year: $year);
    }

    $rates = app(MediterraneanNisPatterns::class)->introductionRates(since: 2011);

    // Years 2011–2020 hold 2, 0 … 0, 1: mean 0.3, sample sd ≈ 0.675, se ≈ 0.21.
    expect($rates['cycles'])->toBe(['2011–2020'])
        ->and($rates['series']['Mediterranean'])->toBe(['mean' => [0.3], 'se' => [0.21]])
        ->and($rates['series']['WMED'])->toBe(['mean' => [0.0], 'se' => [0.0]]);
});
