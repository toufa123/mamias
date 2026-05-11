<?php

declare(strict_types=1);

use App\Models\Literature;
use App\Enums\LiteratureType;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('generates the first code when no records exist', function () {
    expect(Literature::generateNextCode())->toBe('mamias000001');
});

it('generates sequential codes', function () {
    Literature::factory()->create(['code' => 'mamias000001']);
    expect(Literature::generateNextCode())->toBe('mamias000002');
    
    Literature::factory()->create(['code' => 'mamias000042']);
    expect(Literature::generateNextCode())->toBe('mamias000043');
});

it('handles non-standard codes in the database', function () {
    Literature::factory()->create(['code' => 'mamias000001']);
    Literature::factory()->create(['code' => 'other-code']);
    expect(Literature::generateNextCode())->toBe('mamias000002');
});

it('automatically assigns code on creation via observer', function () {
    $literature = Literature::create([
        'short_ref' => 'Smith, 2024',
        'full_ref' => 'Smith, J. (2024). Test. Journal.',
        'type' => LiteratureType::ARTICLE,
    ]);
    
    expect($literature->code)->toBe('mamias000001');
});

it('does not overwrite existing code on creation', function () {
    $literature = Literature::create([
        'code' => 'custom-code',
        'short_ref' => 'Smith, 2024',
        'full_ref' => 'Smith, J. (2024). Test. Journal.',
        'type' => LiteratureType::ARTICLE,
    ]);
    
    expect($literature->code)->toBe('custom-code');
});
