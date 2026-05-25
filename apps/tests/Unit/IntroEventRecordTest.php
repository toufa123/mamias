<?php

declare(strict_types=1);

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Models\IntroEventRecord;
use App\Models\Literature;
use App\Models\Taxon;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('creates an intro event record with correct casts', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = IntroEventRecord::factory()->create([
        'nis_status' => NisStatus::Cryptogenic,
        'establishment_status' => EstablishmentStatus::Invasive,
    ]);

    expect($record->nis_status)->toBe(NisStatus::Cryptogenic)
        ->and($record->establishment_status)->toBe(EstablishmentStatus::Invasive)
        ->and($record->first_introduction_year)->toBeString();
});

it('belongs to a taxon', function () {
    $taxon = Taxon::factory()->create();
    $record = IntroEventRecord::factory()->for($taxon)->create();

    expect($record->taxon)->not->toBeNull()
        ->and($record->taxon->id)->toBe($taxon->id);
});

it('belongs to literature', function () {
    $literature = Literature::factory()->create();
    $record = IntroEventRecord::factory()->for($literature)->create();

    expect($record->literature)->not->toBeNull()
        ->and($record->literature->id)->toBe($literature->id);
});

it('has many subregion records', function () {
    $record = IntroEventRecord::factory()->hasSubregionRecords(3)->create();

    expect($record->subregionRecords)->toHaveCount(3);
});

it('has many pathway records', function () {
    $record = IntroEventRecord::factory()->hasPathwayRecords(2)->create();

    expect($record->pathwayRecords)->toHaveCount(2);
});

it('applies userstamps on creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = IntroEventRecord::factory()->create();

    expect($record->created_by)->toBe($user->id)
        ->and($record->updated_by)->toBe($user->id);
});
