<?php

declare(strict_types=1);

use App\Models\Taxon;
use App\Services\TaxonMatcher;

function matcher(): TaxonMatcher
{
    // A fresh instance per assertion: the real one is a singleton that indexes
    // the catalogue once, which would go stale as each test adds taxa.
    return new TaxonMatcher;
}

it('matches an exact catalogue name', function (): void {
    $taxon = Taxon::factory()->create(['scientificname' => 'Pterois miles']);

    expect(matcher()->match('Pterois miles'))
        ->toMatchArray(['taxon_id' => $taxon->id, 'how' => 'exact']);
});

it('matches regardless of case and surrounding space', function (): void {
    $taxon = Taxon::factory()->create(['scientificname' => 'Pterois miles']);

    expect(matcher()->match('  pterois MILES ')['taxon_id'])->toBe($taxon->id);
});

it('strips the authority the file carries inline', function (): void {
    // Subregion sheets write "Charybdis (Charybdis) feriata (Linnaeus, 1758)".
    $taxon = Taxon::factory()->create(['scientificname' => 'Charybdis feriata']);

    expect(matcher()->match('Charybdis (Charybdis) feriata (Linnaeus, 1758)'))
        ->toMatchArray(['taxon_id' => $taxon->id, 'how' => 'accepted']);
});

it('matches through a synonym recorded in the catalogue', function (): void {
    $taxon = Taxon::factory()->create([
        'scientificname' => 'Acanthaster planci',
        'synonyms_data' => [
            ['AphiaID' => 213290, 'scientificname' => 'Acanthaster echinites', 'authority' => '(Ellis & Solander, 1786)'],
        ],
    ]);

    $result = matcher()->match('Acanthaster echinites (Ellis & Solander, 1786)');

    expect($result['taxon_id'])->toBe($taxon->id)
        ->and($result['how'])->toBe('synonym')
        // The reviewer is shown a different name than the file had, so it is
        // explained rather than applied silently.
        ->and($result['reason'])->toContain('synonym');
});

it('refuses a name that two catalogue taxa reduce to', function (): void {
    // taxas.scientificname is UNIQUE, so identical binomials cannot coexist.
    // Ambiguity arrives instead when two distinct catalogue names reduce to
    // the same genus + species — here the species and an infraspecific taxon.
    // Picking either would write records against the wrong one, with nothing
    // downstream to reveal it.
    Taxon::factory()->create(['scientificname' => 'Mitrella minor']);
    Taxon::factory()->create(['scientificname' => 'Mitrella minor var. tenuis']);

    $result = matcher()->match('Mitrella minor (Scacchi, 1836)');

    expect($result['taxon_id'])->toBeNull()
        ->and($result['how'])->toBe('ambiguous')
        ->and($result['reason'])->toContain('more than one');
});

it('refuses an uncertain determination', function (): void {
    Taxon::factory()->create(['scientificname' => 'Dendostrea folium']);

    $result = matcher()->match('Dendostrea cf folium (Linnaeus, 1758)');

    expect($result['taxon_id'])->toBeNull()
        ->and($result['how'])->toBe('open_nomenclature');
})->with([
    'Dendostrea cf folium',
    'Dendostrea aff. folium',
    'Dendostrea sp.',
]);

it('reports a name absent from the catalogue', function (): void {
    Taxon::factory()->create(['scientificname' => 'Pterois miles']);

    $result = matcher()->match('Lumbrineris perkinsi Carrera-Parra, 2001');

    expect($result['taxon_id'])->toBeNull()
        ->and($result['how'])->toBe('unmatched')
        ->and($result['reason'])->toContain('No catalogue taxon');
});

it('reports a name it cannot read a binomial from', function (): void {
    expect(matcher()->match('Rhodophyta')['how'])->toBe('unparseable');
});
