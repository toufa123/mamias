<?php

declare(strict_types=1);

use App\Enums\Environment;
use Tests\TestCase;

uses(TestCase::class);

it('returns all environment labels', function () {
    expect(Environment::marine->getLabel())->toBe('Marine')
        ->and(Environment::freshwater->getLabel())->toBe('Freshwater')
        ->and(Environment::brackish->getLabel())->toBe('Brackish')
        ->and(Environment::terrestrial->getLabel())->toBe('Terrestrial');
});

it('returns all environment colors', function () {
    expect(Environment::marine->getColor())->toBe('#4166F5')
        ->and(Environment::freshwater->getColor())->toBe('#45ADA8')
        ->and(Environment::brackish->getColor())->toBe('#8E7F73')
        ->and(Environment::terrestrial->getColor())->toBe('#A0A0A0');
});

it('returns all environment icons', function () {
    expect(Environment::marine->getIcon())->toBe('tabler-waves')
        ->and(Environment::freshwater->getIcon())->toBe('tabler-droplet')
        ->and(Environment::brackish->getIcon())->toBe('tabler-ripple')
        ->and(Environment::terrestrial->getIcon())->toBe('tabler-mountain');
});

it('extracts environments from worms response flags', function () {
    $result = Environment::fromWormsData([
        'isMarine' => true,
        'isBrackish' => false,
        'isFreshwater' => true,
        'isTerrestrial' => false,
    ]);

    expect($result)->toBe(['marine', 'freshwater']);
});

it('returns empty array when no worms flags are set', function () {
    expect(Environment::fromWormsData([]))->toBe([]);
});

it('parses environment from label or value', function () {
    expect(Environment::fromLabelOrValue('marine'))->toBe(Environment::marine)
        ->and(Environment::fromLabelOrValue('Marine'))->toBe(Environment::marine)
        ->and(Environment::fromLabelOrValue(Environment::freshwater))->toBe(Environment::freshwater)
        ->and(Environment::fromLabelOrValue(null))->toBeNull()
        ->and(Environment::fromLabelOrValue(''))->toBeNull();
});

it('provides label alias via label method', function () {
    expect(Environment::marine->label())->toBe('Marine');
});
