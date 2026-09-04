<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\PathwayType;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('creates a pathway record with correct casts', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = PathwayRecord::factory()->create([
        'category' => CbdPathwayCategory::ReleaseIntoNature,
        'subcategory' => CbdPathwaySubcategory::Release_1_1,
        'pathway_type' => PathwayType::Primary,
        'uncertainty' => DataQuality::High,
    ]);

    expect($record->category)->toBe(CbdPathwayCategory::ReleaseIntoNature)
        ->and($record->subcategory)->toBe(CbdPathwaySubcategory::Release_1_1)
        ->and($record->pathway_type)->toBe(PathwayType::Primary)
        ->and($record->uncertainty)->toBe(DataQuality::High);
});

it('belongs to an intro event record', function () {
    $introEvent = IntroEventRecord::factory()->create();
    $record = PathwayRecord::factory()->for($introEvent, 'introEvent')->create();

    expect($record->introEvent)->not->toBeNull()
        ->and($record->introEvent->id)->toBe($introEvent->id);
});

it('applies userstamps on creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $record = PathwayRecord::factory()->create();

    expect($record->created_by)->toBe($user->id)
        ->and($record->updated_by)->toBe($user->id);
});
