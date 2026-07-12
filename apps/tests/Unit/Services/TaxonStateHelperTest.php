<?php

declare(strict_types=1);

use App\Services\TaxonNormalizer;
use App\Services\TaxonStateHelper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->helper = new TaxonStateHelper(new TaxonNormalizer);
});

it('returns empty array when states have no differences', function () {
    $state = ['scientificname' => 'Testus', 'authority' => null, 'aphia_id' => null];

    $changed = $this->helper->getChangedFieldLabels($state, $state);

    expect($changed)->toBe([]);
});

it('detects changed fields between states', function () {
    $current = ['scientificname' => 'Old name', 'authority' => 'Old auth'];
    $incoming = ['scientificname' => 'New name', 'authority' => 'New auth'];

    $changed = $this->helper->getChangedFieldLabels($current, $incoming);

    expect($changed)->toBe(['Scientific Name', 'Authority']);
});

it('returns field labels for known and unknown fields', function () {
    expect($this->helper->getFetchedDataFieldLabel('scientificname'))->toBe('Scientific Name')
        ->and($this->helper->getFetchedDataFieldLabel('aphia_id'))->toBe('Aphia ID')
        ->and($this->helper->getFetchedDataFieldLabel('unknown_field'))->toBe('Unknown Field');
});

it('builds fetched data states with normalized values', function () {
    Http::fake(); // prevent external calls

    $currentValues = [
        'scientificname' => '  Testus  ',
        'aphia_id' => '42',
        'worms_status' => 'accepted',
        'catalogue_status' => 'checked & accepted',
        'environments' => ['marine'],
        'is_extinct' => false,
        'synonyms_data' => null,
        'Easin_id' => null,
        'notes' => null,
        'proposed_accepted_name' => null,
        'authority' => null,
        'url' => null,
        'lsid' => null,
        'unacceptreason' => null,
        'kingdom' => null,
        'phylum' => null,
        'class' => null,
        'order' => null,
        'family' => null,
        'genus' => null,
        'rank' => null,
    ];

    $wormsRecord = [
        'scientificname' => 'Testus',
        'AphiaID' => 42,
        'status' => 'accepted',
    ];

    [$currentState, $incomingState] = $this->helper->buildFetchedDataStates($currentValues, $wormsRecord, [], null);

    expect($currentState['scientificname'])->toBe('Testus')
        ->and($currentState['aphia_id'])->toBe(42)
        ->and($incomingState['scientificname'])->toBe('Testus')
        ->and($incomingState['aphia_id'])->toBe(42)
        ->and($incomingState['catalogue_status'])->toBe('checked & accepted');
});

it('formats worms data for form display', function () {
    $record = [
        'scientificname' => 'Testus',
        'AphiaID' => 42,
        'status' => 'accepted',
        'isExtinct' => 0,
    ];

    $result = $this->helper->formatWormsDataForForm($record, []);

    expect($result['scientificname'])->toBe('Testus')
        ->and($result['aphia_id'])->toBe(42)
        ->and($result['catalogue_status'])->toBe('checked & accepted');
});
