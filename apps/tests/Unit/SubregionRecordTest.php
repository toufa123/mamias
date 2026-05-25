<?php

declare(strict_types=1);

use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('creates a subregion record with correct casts', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = SubregionRecord::factory()->create([
        'subregion' => Subregion::WMED,
        'nis_status' => NisStatus::NIS,
    ]);

    expect($record->subregion)->toBe(Subregion::WMED)
        ->and($record->nis_status)->toBe(NisStatus::NIS)
        ->and($record->first_arrival_year)->toBeString();
});

it('belongs to an intro event record', function () {
    $introEvent = IntroEventRecord::factory()->create();
    $record = SubregionRecord::factory()->for($introEvent)->create();

    expect($record->introEvent)->not->toBeNull()
        ->and($record->introEvent->id)->toBe($introEvent->id);
});

it('applies userstamps on creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = SubregionRecord::factory()->create();

    expect($record->created_by)->toBe($user->id)
        ->and($record->updated_by)->toBe($user->id);
});
