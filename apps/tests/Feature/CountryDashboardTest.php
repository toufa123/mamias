<?php

declare(strict_types=1);

use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use App\Services\MediterraneanDashboard;
use App\Services\MediterraneanNisPatterns;
use Database\Seeders\LayupCountryDashboardSeeder;

/** An event first recorded in $countries, reported in $subregions. */
function firstRecordedIn(array $countries, array $subregions = [], int $year = 2005): IntroEventRecord
{
    $event = IntroEventRecord::factory()->create(['first_country' => $countries, 'first_introduction_year' => $year]);

    foreach ($subregions as $subregion) {
        SubregionRecord::factory()->create(['intro_event_id' => $event->id, 'subregion' => $subregion]);
    }

    return $event;
}

it('ranks countries by first records, co-first records in each and empty countries included', function () {
    firstRecordedIn(['Israel']);
    firstRecordedIn(['Israel', 'Lebanon']);
    firstRecordedIn(['Tunisia']);

    $ranking = collect(app(MediterraneanDashboard::class)->countryRanking())->pluck('value', 'country');

    expect($ranking->take(3)->all())->toBe(['Israel' => 2, 'Lebanon' => 1, 'Tunisia' => 1])
        ->and($ranking['Montenegro'])->toBe(0);
});

it('reports Gaza strip records under Israel, once per event', function () {
    firstRecordedIn(['Gaza strip']);
    firstRecordedIn(['Israel', 'Gaza strip'], [Subregion::EMED]);

    $dashboard = app(MediterraneanDashboard::class);
    $ranking = collect($dashboard->countryRanking())->pluck('value', 'country');

    expect($ranking['Israel'])->toBe(2)
        ->and($ranking->has('Gaza strip'))->toBeFalse()
        ->and($dashboard->forCountry('Israel')->headline()['events'])->toBe(2)
        ->and(app(MediterraneanNisPatterns::class)->forCountry('Israel')->groupsBySubregion()['totals'][0])->toBe(2);
});

it('narrows the headline to a country with its rank and share', function () {
    firstRecordedIn(['Israel']);
    firstRecordedIn(['Israel']);
    firstRecordedIn(['Tunisia']);
    firstRecordedIn(['Tunisia'])->delete();

    expect(app(MediterraneanDashboard::class)->forCountry('Tunisia')->headline())
        ->toMatchArray(['events' => 1, 'country' => 'Tunisia', 'rank' => 2, 'countries' => 2, 'share' => 33.3]);
});

it('sets a country beside its own sub-regions and the basin, and follows where its NIS spread', function () {
    firstRecordedIn(['Tunisia'], [Subregion::CMED, Subregion::WMED]);
    firstRecordedIn(['Israel'], [Subregion::EMED]);

    $patterns = app(MediterraneanNisPatterns::class)->forCountry('Tunisia');
    $shared = $patterns->sharedBySubregion(2000);

    expect(array_column($patterns->pathwayShares()['rows'], 'total', 'name'))->toBe(['Tunisia' => 1, 'WMED' => 1, 'CMED' => 1, 'Mediterranean' => 2])
        ->and(array_combine($shared['combinations'], $shared['counts'][2010]))->toMatchArray(['WMED+CMED' => 1, 'EMED' => 0]);
});

it('publishes the by-country page for the country in the query string', function () {
    firstRecordedIn(['Israel']);
    firstRecordedIn(['Israel']);
    firstRecordedIn(['Tunisia']);
    $this->seed(LayupCountryDashboardSeeder::class);

    $page = $this->get('/pages/dashboard/by-country?country=tunisia')
        ->assertOk()
        ->assertSee('First Mediterranean records in Tunisia');

    expect($page->getContent())->toMatch('/value="Tunisia"\s+selected/');

    // Unknown or missing: the first country alphabetically, the top of the selector.
    $this->get('/pages/dashboard/by-country?country=Atlantis')->assertOk()->assertSee('First Mediterranean records in Albania');
    $this->get('/pages/dashboard/by-country')->assertOk()->assertSee('First Mediterranean records in Albania');
});
