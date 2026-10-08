<?php

use App\Enums\EstablishmentStatus;
use App\Enums\Subregion;
use App\Livewire\NisAreaMap;
use App\Livewire\NisMap;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\SubregionRecord;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

/** An event recorded in the given subregions, with that establishment status there. */
function eventIn(array $subregions, EstablishmentStatus $status = EstablishmentStatus::Established): IntroEventRecord
{
    $event = IntroEventRecord::factory()->create(['establishment_status' => $status]);

    foreach ($subregions as $subregion) {
        SubregionRecord::factory()->for($event, 'introEvent')->create(['subregion' => $subregion, 'establishment_status' => $status]);
    }

    return $event;
}

it('is public', function () {
    get('/pages/map')->assertOk()->assertSee('Aegean-Levantine Sea');
});

it('counts species per subregion and lists the picked subregion\'s species', function () {
    $levantine = eventIn([Subregion::EMED]);
    $both = eventIn([Subregion::EMED, Subregion::WMED]);

    livewire(NisMap::class)
        ->assertSet('subregion', 'EMED')
        ->assertCanSeeTableRecords([$levantine, $both])
        ->call('pickSubregion', 'WMED')
        ->assertCanSeeTableRecords([$both])
        ->assertCanNotSeeTableRecords([$levantine])
        ->assertSee('Species recorded in the Western Mediterranean')
        ->tap(fn ($component) => expect($component->instance()->counts)->toMatchArray(['EMED' => 2, 'WMED' => 1]));
});

it('narrows the counts with the data page filters', function () {
    eventIn([Subregion::EMED], EstablishmentStatus::Established);
    eventIn([Subregion::EMED], EstablishmentStatus::Casual);

    livewire(NisMap::class)
        ->filterTable('establishment_status', [EstablishmentStatus::Casual->value])
        ->tap(fn ($component) => expect($component->instance()->counts)->toBe(['EMED' => 1]));
});

it('ignores unknown subregion codes', function () {
    livewire(NisMap::class, ['subregion' => 'ATLANTIS'])
        ->assertSet('subregion', 'EMED')
        ->call('pickSubregion', 'ATLANTIS')
        ->assertSet('subregion', 'EMED');
});

it('reports a clicked subregion to the page', function () {
    livewire(NisAreaMap::class, ['counts' => ['WMED' => 3], 'selected' => 'EMED'])
        ->call('handleLayerClick', 'subregion-WMED-0123abcd')
        ->assertDispatched('subregion-picked', code: 'WMED');
});

it('has a boundary for every subregion', function () {
    expect(array_keys(NisAreaMap::geometry()))
        ->toEqualCanonicalizing(array_column(Subregion::cases(), 'value'));
});

it('limits the map to the subregions ticked in the EcAp filter', function () {
    $levantine = eventIn([Subregion::EMED]);
    $both = eventIn([Subregion::EMED, Subregion::WMED]);

    livewire(NisMap::class)
        ->filterTable('subregion', [Subregion::WMED->value])
        ->assertSet('subregion', 'WMED')
        ->assertCanSeeTableRecords([$both])
        ->assertCanNotSeeTableRecords([$levantine])
        ->tap(fn ($component) => expect($component->instance()->counts)->toBe(['WMED' => 1]))
        ->call('pickSubregion', 'EMED')
        ->assertSet('subregion', 'WMED');
});

it('counts and lists species by country of first record on the countries layer', function () {
    $israel = IntroEventRecord::factory()->create(['first_country' => ['Israel']]);
    $both = IntroEventRecord::factory()->create(['first_country' => ['Israel', 'Lebanon']]);
    $italy = IntroEventRecord::factory()->create(['first_country' => ['Italy']]);

    livewire(NisMap::class)
        ->call('showLayer', 'countries')
        ->assertSet('country', 'Israel')
        ->assertCanSeeTableRecords([$israel, $both])
        ->assertCanNotSeeTableRecords([$italy])
        ->assertSee('Species first recorded in Israel')
        ->tap(fn ($component) => expect($component->instance()->countryCounts)->toMatchArray(['Israel' => 2, 'Lebanon' => 1, 'Italy' => 1]))
        ->call('pickCountry', 'Italy')
        ->assertSet('country', 'Italy')
        ->assertCanSeeTableRecords([$italy])
        ->assertCanNotSeeTableRecords([$israel, $both])
        ->call('pickCountry', 'Atlantis')
        ->assertSet('country', 'Italy');
});

it('pins approved occurrences of the filtered species only when asked', function () {
    $event = IntroEventRecord::factory()->create();
    $approved = Occurrence::factory()->approved()->for($event)->create(['location' => [['lat' => 36.5, 'lng' => 14.25]]]);
    Occurrence::factory()->pending()->for($event)->create(['location' => [['lat' => 40.1, 'lng' => 5.5]]]);

    livewire(NisMap::class)
        ->tap(fn ($component) => expect($component->instance()->occurrenceIds)->toBe([]))
        ->set('pins', true)
        ->tap(fn ($component) => expect($component->instance()->occurrenceIds)->toBe([$approved->getKey()]));
});

it('sends one bubble per country anchor, the picked one marked', function () {
    livewire(NisAreaMap::class, ['layer' => 'countries', 'countryCounts' => ['Lebanon' => 2, 'Italy' => 5, 'Atlantis' => 1], 'selected' => 'Lebanon'])
        ->assertDispatched('nis-bubbles', fn (string $event, array $params): bool => array_column($params['bubbles'], 'name') === ['Italy', 'Lebanon']
            && $params['bubbles'][1]['selected'] === true
            && [$params['bubbles'][0]['lat'], $params['bubbles'][0]['lng']] === NisAreaMap::COUNTRY_ANCHORS['Italy']);

    livewire(NisAreaMap::class, ['layer' => 'subregions', 'countryCounts' => ['Italy' => 5]])
        ->assertDispatched('nis-bubbles', bubbles: []);
});

it('counts and lists Gaza strip records under Israel, as the dashboards do', function () {
    $israel = IntroEventRecord::factory()->create(['first_country' => ['Israel']]);
    $gaza = IntroEventRecord::factory()->create(['first_country' => ['Gaza strip']]);
    $both = IntroEventRecord::factory()->create(['first_country' => ['Israel', 'Gaza strip']]);

    livewire(NisMap::class)
        ->call('showLayer', 'countries')
        ->assertSet('country', 'Israel')
        ->assertCanSeeTableRecords([$israel, $gaza, $both])
        ->tap(fn ($component) => expect($component->instance()->countryCounts)->toBe(['Israel' => 3]))
        ->call('pickCountry', 'Gaza strip')
        ->assertSet('country', 'Israel');

    expect(NisAreaMap::COUNTRY_ANCHORS)->toHaveKey('Israel')->not->toHaveKey('Gaza strip');
});
