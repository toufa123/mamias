<?php

declare(strict_types=1);

use App\Enums\Subregion;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Models\IntroEventRecord;
use App\Models\Taxon;
use App\Services\IntroEventReviewResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * A flagged event whose review note lists the given "Label: raw" cells, as the importer writes it.
 *
 * @param  list<string>  $cells
 */
function flaggedEvent(string $species, array $cells, ?int $year = null): IntroEventRecord
{
    // Scientific names are unique: several events of one species share its taxon.
    $taxon = Taxon::firstWhere('scientificname', $species) ?? Taxon::factory()->create(['scientificname' => $species]);

    $event = IntroEventRecord::factory()
        ->for($taxon)
        ->create(['first_introduction_year' => $year, 'notes' => IntroEventRecordImporter::REVIEW_NOTE_PREFIX.implode('; ', $cells)]);
    $event->needs_review = true;
    $event->save();

    return $event;
}

/** EASIN answers for one species: its first EU records as [country, year] pairs. */
function fakeEasin(string $species, array $firstRecords): void
{
    Http::fake(['easin.jrc.ec.europa.eu/*' => Http::response([[
        'EASINID' => 'R00001',
        'Name' => $species,
        'FirstIntroductionsInEU' => array_map(fn (array $record): array => ['Country' => $record[0], 'Year' => (string) $record[1]], $firstRecords),
    ]])]);
}

it('reads ambiguous years by explicit rules', function (string $raw, ?int $year, string $category) {
    [$proposed, $actualCategory] = IntroEventReviewResolver::readYear($raw);

    expect([$proposed, $actualCategory])->toBe([$year, $category]);
})->with([
    ['1972-74', 1972, 'range'],
    ['1972-1974', 1972, 'range'],
    ['2003-4', 2003, 'range'],
    ['2003/2004', 2003, 'range'],
    ['<2007', 2007, 'before'],
    ['≤2016', 2016, 'before'],
    ['2004?', 2004, 'approximate'],
    ['~ 1990', 1990, 'approximate'],
    ['1970s', 1970, 'decade'],
    ["1990's", 1990, 'decade'],
    ['1965-67 not 1985', 1965, 'correction'],
    ['1973 not 1979', 1973, 'correction'],
    ['1791', 1791, 'historical'],
    ['2016-14', null, 'manual'],
    ['RS-ION', null, 'manual'],
]);

it('applies only the chosen categories, never a conflict, and clears the flag once every cell is answered', function () {
    fakeEasin('Crepidula fornicata', [['HR', 1990]]);

    $clear = flaggedEvent('Crepidula fornicata', ['First Introduction Year: 1972-74', 'WMED First Arrival Year: 1980-82']);
    $optIn = flaggedEvent('Crepidula fornicata', ['First Introduction Year: 1985-86', 'EMED First Arrival Year: <1999']);
    // Croatia dates the Adriatic: EASIN 1990 is earlier than the proposed 1995.
    $conflict = flaggedEvent('Crepidula fornicata', ['Adriatic First Arrival Year: 1995-96'], year: 1980);

    $result = app(IntroEventReviewResolver::class)->apply(IntroEventReviewResolver::SAFE_CATEGORIES);

    expect($result)->toBe(['applied' => 3, 'events_cleared' => 1])
        ->and($clear->refresh()->needs_review)->toBeFalse()
        ->and($clear->first_introduction_year)->toBe(1972)
        ->and($clear->subregionRecords()->where('subregion', Subregion::WMED)->value('first_arrival_year'))->toBe(1980)
        ->and($clear->notes)->toContain('Resolved on review')->toContain("WMED First Arrival Year '1980-82' → 1980")
        // "<1999" is opt-in, so the event stays flagged with only its range applied.
        ->and($optIn->refresh()->needs_review)->toBeTrue()
        ->and($optIn->first_introduction_year)->toBe(1985)
        ->and($conflict->refresh()->needs_review)->toBeTrue()
        ->and($conflict->subregionRecords()->where('subregion', Subregion::ADRIA)->exists())->toBeFalse();

    // A second run with the opt-in category finishes the event without re-applying the range.
    $second = app(IntroEventReviewResolver::class)->apply([...IntroEventReviewResolver::SAFE_CATEGORIES, 'before']);

    expect($second)->toBe(['applied' => 1, 'events_cleared' => 1])
        ->and($optIn->refresh()->needs_review)->toBeFalse()
        ->and($optIn->subregionRecords()->where('subregion', Subregion::EMED)->value('first_arrival_year'))->toBe(1999)
        ->and(substr_count($optIn->notes, "'1985-86'"))->toBe(1);
});

it('dates an undated event from its earliest resolved sub-region year', function () {
    fakeEasin('Heliacus implexus', []);
    $event = flaggedEvent('Heliacus implexus', ['First Introduction Year: RS-ION', 'EMED First Arrival Year: 2019?']);

    $basin = collect(app(IntroEventReviewResolver::class)->proposalsFor($event))->firstWhere('field', 'First Introduction Year');

    expect($basin)->toMatchArray(['proposed' => 2019, 'category' => 'derived', 'conflict' => false]);
});

it('leaves the database untouched on a dry run', function () {
    Storage::fake();
    fakeEasin('Crepidula fornicata', []);
    $event = flaggedEvent('Crepidula fornicata', ['First Introduction Year: 1972-74']);

    $this->artisan('intro-events:review')->assertSuccessful();

    expect($event->refresh()->needs_review)->toBeTrue()
        ->and($event->first_introduction_year)->toBeNull()
        ->and(Storage::files('review'))->toHaveCount(1);
});
