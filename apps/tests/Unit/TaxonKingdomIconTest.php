<?php

declare(strict_types=1);

use App\Models\Taxon;

it('gives each catalogued kingdom its own icon, and any other the generic one', function () {
    expect(array_unique(Taxon::KINGDOM_ICONS))->toHaveCount(count(Taxon::KINGDOM_ICONS))
        ->and(Taxon::kingdomIcon('Animalia'))->toBe('tabler-paw')
        ->and(Taxon::kingdomIcon('Fungi'))->toBe('tabler-hierarchy-2')
        ->and(Taxon::kingdomIcon(null))->toBe('tabler-hierarchy-2');
});
