<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Taxon;
use App\Enums\Worms_Status;
use App\Enums\Catalogue_Status;
use Tests\TestCase;

uses(TestCase::class);

it('can create a taxon and cast attributes correctly', function () {
    $taxon = Taxon::factory()->create([
        'scientificname' => 'Caretta caretta',
        'worms_status' => Worms_Status::accepted,
        'catalogue_status' => Catalogue_Status::checked_accepted,
        'environments' => ['marine', 'brackish'],
        'is_extinct' => false,
    ]);

    expect($taxon->scientificname)->toBe('Caretta caretta')
        ->and($taxon->worms_status)->toBe(Worms_Status::accepted)
        ->and($taxon->catalogue_status)->toBe(Catalogue_Status::checked_accepted)
        ->and($taxon->environments)->toBe(['marine', 'brackish'])
        ->and($taxon->is_extinct)->toBeFalse();
});

it('handles synonyms_data as an array', function () {
    $synonyms = [
        ['AphiaID' => 123, 'scientificname' => 'Synonym 1', 'status' => 'unaccepted'],
    ];

    $taxon = Taxon::factory()->create([
        'synonyms_data' => $synonyms,
    ]);

    $this->assertDatabaseHas('taxas', [
        'id' => $taxon->id,
    ]);

    $found = Taxon::find($taxon->id);
    expect($found->synonyms_data)->toBe($synonyms);
});

it('applies userstamps on creation', function () {
    // Assuming a user is authenticated or using a mock
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    $taxon = Taxon::factory()->create();

    expect($taxon->created_by)->toBe($user->id)
        ->and($taxon->updated_by)->toBe($user->id);
});
