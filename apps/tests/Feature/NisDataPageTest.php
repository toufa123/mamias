<?php

use App\Livewire\NisData;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('lists introduction events publicly, linking each species to its page', function () {
    $event = IntroEventRecord::factory()->create();

    get('/pages/data')->assertOk();

    livewire(NisData::class)
        ->assertCanSeeTableRecords([$event])
        ->assertSee(route('data.species', $event), false);
});

it('leaves events of species deleted from the catalogue out of the table and without a page', function () {
    $event = IntroEventRecord::factory()->create();
    $event->taxon->delete();

    livewire(NisData::class)->assertCanNotSeeTableRecords([$event]);
    get(route('data.species', $event))->assertNotFound();
});

it('shows the catalogue and event data without the internal notes', function () {
    $event = IntroEventRecord::factory()->create(['notes' => 'Curator-only remark']);

    get(route('data.species', $event))
        ->assertOk()
        ->assertSee($event->taxon->scientificname)
        ->assertSee('Synonyms')
        ->assertSee('References')
        ->assertSee('Introduction event')
        ->assertDontSee('Curator-only remark')
        ->assertSee('No occurrence has been recorded for this species yet.');
});

it('names and describes the species in the page title and its link preview', function () {
    $event = IntroEventRecord::factory()->create([
        'first_introduction_year' => 2003,
        'first_country' => ['Türkiye'],
    ]);
    $event->taxon->update(['scientificname' => 'Acteocina crithodes', 'authority' => '(Melvill & Standen, 1901)', 'phylum' => 'Mollusca']);

    get(route('data.species', $event))
        ->assertOk()
        // Escaped exactly once: "&amp;", never "&amp;amp;".
        ->assertSee('<title>MAMIAS :: Acteocina crithodes (Melvill &amp; Standen, 1901) | Since 2012</title>', false)
        ->assertSee('content="Acteocina crithodes (Melvill &amp; Standen, 1901) — MAMIAS"', false)
        ->assertSee('content="Acteocina crithodes (Mollusca): non-indigenous species in the Mediterranean, first recorded in 2003 (Türkiye).', false)
        ->assertDontSee('&amp;amp;', false)
        ->assertSee(asset('images/og-image.png'), false)
        ->assertDontSee('<base', false);
});

it('pins approved occurrences only, with escaped popups', function () {
    $event = IntroEventRecord::factory()->create();
    Occurrence::factory()->approved()->for($event)->create([
        'location' => [['lat' => 36.5, 'lng' => 14.25]],
        'habitats' => ['<script>alert(1)</script>'],
    ]);
    Occurrence::factory()->pending()->for($event)->create([
        'location' => [['lat' => 40.125, 'lng' => 5.5]],
    ]);

    $html = get(route('data.species', $event))->assertOk()->getContent();

    expect($html)
        ->toContain('1 approved occurrence')
        ->toContain('36.50000, 14.25000')
        ->not->toContain('40.12500, 5.50000')
        ->not->toContain('<script>alert(1)</script>');
});
