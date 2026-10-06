<?php

declare(strict_types=1);

use App\Models\Taxon;
use Tests\TestCase;

uses(TestCase::class);

it('gives each catalogued kingdom its own icon, and any other the generic one', function () {
    expect(array_unique(Taxon::KINGDOM_ICONS))->toHaveCount(count(Taxon::KINGDOM_ICONS))
        ->and(Taxon::kingdomIcon('Animalia'))->toBe('marine-fish')
        ->and(Taxon::kingdomIcon('Fungi'))->toBe('tabler-hierarchy-2')
        ->and(Taxon::kingdomIcon(null))->toBe('tabler-hierarchy-2');
});

it('resolves every kingdom icon from the marine set', function () {
    foreach (Taxon::KINGDOM_ICONS as $icon) {
        expect(svg($icon)->toHtml())->toContain('<svg');
    }
});
