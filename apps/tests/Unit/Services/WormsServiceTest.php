<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use App\Services\WormsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->service = new WormsService;
});

it('returns null when getRecordByAphiaID gets 204', function () {
    Http::fake([
        'marinespecies.org/*' => Http::response(null, 204),
    ]);

    expect($this->service->getRecordByAphiaID(999999))->toBeNull();
});

it('returns record data when getRecordByAphiaID succeeds', function () {
    Http::fake([
        'marinespecies.org/*' => Http::response([
            'AphiaID' => 1234,
            'scientificname' => 'Testus species',
            'status' => 'accepted',
            'rank' => 'Species',
            'kingdom' => 'Animalia',
        ], 200),
    ]);

    $result = $this->service->getRecordByAphiaID(1234);

    expect($result)->toBeArray()
        ->and($result['AphiaID'])->toBe(1234)
        ->and($result['scientificname'])->toBe('Testus species');
});

it('returns empty array for searchSpecies with short query', function () {
    expect($this->service->searchSpecies('ab'))->toBe([]);
});

it('returns results from searchSpecies', function () {
    Http::fake([
        'marinespecies.org/rest/AphiaRecordsByName/*' => Http::response([
            ['AphiaID' => 1, 'scientificname' => 'Testus species'],
        ], 200),
    ]);

    $result = $this->service->searchSpecies('Testus');

    expect($result)->toBeArray()
        ->and($result[0]['scientificname'])->toBe('Testus species');
});

it('returns empty array on failed searchSpecies', function () {
    Http::fake([
        'marinespecies.org/*' => Http::response(null, 500),
    ]);

    expect($this->service->searchSpecies('Gadus'))->toBe([]);
});

it('returns empty array on 204 from getSynonyms', function () {
    Http::fake([
        'marinespecies.org/*' => Http::response(null, 204),
    ]);

    expect($this->service->getSynonyms(1))->toBe([]);
});

it('returns synonyms array', function () {
    Http::fake([
        'marinespecies.org/rest/AphiaSynonymsByAphiaID/*' => Http::response([
            ['AphiaID' => 2, 'scientificname' => 'Synonymus oldi', 'status' => 'unaccepted'],
        ], 200),
    ]);

    $synonyms = $this->service->getSynonyms(1);

    expect($synonyms)->toBeArray()
        ->and($synonyms[0]['scientificname'])->toBe('Synonymus oldi');
});

it('populates taxon from worms data', function () {
    Http::fake([
        'marinespecies.org/*' => Http::sequence()
            ->push([['AphiaID' => 10, 'scientificname' => 'Synonymus', 'status' => 'unaccepted']], 200) // synonyms
            ->pushStatus(204), // empty cache for the rest
    ]);

    $taxon = new Taxon;
    $data = [
        'AphiaID' => 100,
        'scientificname' => 'Testus validus',
        'authority' => 'Smith, 2020',
        'status' => 'accepted',
        'rank' => 'Species',
        'kingdom' => 'Animalia',
        'phylum' => 'Chordata',
        'class' => 'Mammalia',
        'order' => 'Primates',
        'family' => 'Hominidae',
        'genus' => 'Homo',
        'url' => 'http://marinespecies.org/aphia.php?p=taxdetails&id=100',
        'isExtinct' => false,
        'isMarine' => true,
    ];

    $this->service->populateTaxonFromWorms($taxon, $data);

    expect($taxon->aphia_id)->toBe(100)
        ->and($taxon->scientificname)->toBe('Testus validus')
        ->and($taxon->authority)->toBe('Smith, 2020')
        ->and($taxon->rank)->toBe('Species')
        ->and($taxon->worms_status)->toBe(Worms_Status::accepted)
        ->and($taxon->catalogue_status)->toBe(Catalogue_Status::checked_accepted)
        ->and($taxon->environments)->toBe(['marine'])
        ->and($taxon->lsid)->toBe('urn:lsid:marinespecies.org:taxname:100')
        ->and($taxon->fetched_at)->not->toBeNull();
});

it('redirects to accepted name when handling unaccepted worms data', function () {
    Http::fake([
        'marinespecies.org/rest/AphiaRecordByAphiaID/*' => Http::response([
            'AphiaID' => 200,
            'scientificname' => 'Testus correctus',
            'status' => 'accepted',
            'rank' => 'Species',
        ], 200),
        'marinespecies.org/rest/AphiaSynonymsByAphiaID/*' => Http::response(null, 204),
    ]);

    $taxon = new Taxon;
    $data = [
        'AphiaID' => 100,
        'scientificname' => 'Testus wrongus',
        'authority' => 'Jones, 1990',
        'status' => 'unaccepted',
        'valid_AphiaID' => 200,
    ];

    $this->service->populateTaxonFromWorms($taxon, $data);

    expect($taxon->aphia_id)->toBe(200)
        ->and($taxon->scientificname)->toBe('Testus correctus')
        ->and($taxon->notes)->toContain('unaccepted');
});

it('expands synonyms with filtered fields', function () {
    $taxon = Taxon::factory()->make(['aphia_id' => 50]);

    Http::fake([
        'marinespecies.org/rest/AphiaSynonymsByAphiaID/50' => Http::response([
            ['AphiaID' => 51, 'scientificname' => 'Syn 1', 'authority' => 'A', 'status' => 'unaccepted', 'unacceptreason' => 'misapplied', 'extra' => 'ignored'],
        ], 200),
    ]);

    $count = $this->service->expandSynonyms($taxon, persist: false);

    expect($count)->toBe(1)
        ->and($taxon->synonyms_data[0])->toHaveKeys(['AphiaID', 'scientificname', 'authority', 'status', 'unacceptreason'])
        ->and($taxon->synonyms_data[0])->not->toHaveKey('extra');
});

it('returns grouped phyla from cache', function () {
    Http::fake([
        'marinespecies.org/rest/AphiaChildrenByAphiaID/*' => Http::response([
            ['AphiaID' => 100, 'scientificname' => 'Testphylum', 'rank' => 'Phylum', 'status' => 'accepted'],
        ], 200),
    ]);

    $phyla = $this->service->getPhyla();

    expect($phyla)->toBeArray();
    $allPhyla = array_values($phyla);
    expect($allPhyla)->toBeArray();
});
