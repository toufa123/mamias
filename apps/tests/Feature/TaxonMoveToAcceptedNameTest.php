<?php

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\IntroEventRecord;
use App\Models\Taxon;
use App\Models\User;
use App\Services\AcceptedNameConfidence;
use App\Services\TaxonNormalizer;
use App\Services\TaxonService;
use App\Services\WormsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

/**
 * WoRMS as it answers for Sepia pharaonis (superseded combination) and its
 * accepted name Acanthosepion pharaonis.
 */
function fakeWormsSepia(): void
{
    Http::fake([
        '*/AphiaRecordByAphiaID/181376' => Http::response([
            'AphiaID' => 181376, 'scientificname' => 'Sepia pharaonis', 'status' => 'superseded combination',
            'valid_AphiaID' => 1624463, 'valid_name' => 'Acanthosepion pharaonis',
        ]),
        '*/AphiaRecordByAphiaID/1624463' => Http::response([
            'AphiaID' => 1624463, 'scientificname' => 'Acanthosepion pharaonis', 'authority' => '(Ehrenberg, 1831)',
            'status' => 'accepted', 'valid_AphiaID' => 1624463, 'valid_name' => 'Acanthosepion pharaonis',
            'rank' => 'Species', 'kingdom' => 'Animalia', 'phylum' => 'Mollusca', 'class' => 'Cephalopoda',
            'order' => 'Sepiida', 'family' => 'Sepiidae', 'genus' => 'Acanthosepion', 'isMarine' => 1,
        ]),
        '*/AphiaSynonymsByAphiaID/*' => Http::response([
            ['AphiaID' => 181376, 'scientificname' => 'Sepia pharaonis', 'status' => 'superseded combination'],
        ]),
    ]);
}

function sepiaPharaonis(): Taxon
{
    return Taxon::factory()->create([
        'scientificname' => 'Sepia pharaonis',
        'aphia_id' => 181376,
        'worms_status' => Worms_Status::superseded_combination,
        'catalogue_status' => Catalogue_Status::checked_not_accepted,
        'proposed_accepted_name' => 'Acanthosepion pharaonis',
    ]);
}

it('renames the taxon in place and keeps the recorded name on its events', function () {
    fakeWormsSepia();
    $taxon = sepiaPharaonis();
    $event = IntroEventRecord::factory()->create(['taxon_id' => $taxon->id]);

    $result = app(TaxonService::class)->moveToAcceptedName($taxon);

    expect($result->id)->toBe($taxon->id)
        ->and($result->scientificname)->toBe('Acanthosepion pharaonis')
        ->and($result->aphia_id)->toBe(1624463)
        ->and($result->catalogue_status)->toBe(Catalogue_Status::checked_accepted)
        ->and($result->proposed_accepted_name)->toBeNull()
        ->and($result->notes)->toContain('Previously catalogued as Sepia pharaonis')
        ->and($event->fresh()->taxon_id)->toBe($taxon->id)
        ->and($event->fresh()->verbatim_name)->toBe('Sepia pharaonis');
});

it('merges into the accepted taxon when it is already catalogued', function () {
    fakeWormsSepia();
    $taxon = sepiaPharaonis();
    $accepted = Taxon::factory()->create(['scientificname' => 'Acanthosepion pharaonis', 'aphia_id' => 1624463]);
    $event = IntroEventRecord::factory()->create(['taxon_id' => $taxon->id]);

    $result = app(TaxonService::class)->moveToAcceptedName($taxon);

    expect($result->id)->toBe($accepted->id)
        ->and($event->fresh()->taxon_id)->toBe($accepted->id)
        ->and($event->fresh()->verbatim_name)->toBe('Sepia pharaonis')
        ->and($taxon->fresh()->trashed())->toBeTrue();
});

it('refuses when WoRMS gives no other accepted name', function () {
    Http::fake(['*/AphiaRecordByAphiaID/141717' => Http::response([
        'AphiaID' => 141717, 'scientificname' => 'Metaxia bacillum', 'status' => 'unreplaced junior homonym',
        'valid_AphiaID' => 141717, 'valid_name' => 'Metaxia bacillum',
    ])]);
    $taxon = Taxon::factory()->create(['scientificname' => 'Metaxia bacillum', 'aphia_id' => 141717]);

    app(TaxonService::class)->moveToAcceptedName($taxon);
})->throws(RuntimeException::class);

it('treats a subgenus or nominal subspecies as the same binomial', function (string $name, string $other, bool $same) {
    expect(TaxonNormalizer::isSameBinomial($name, $other))->toBe($same);
})->with([
    ['Penaeus japonicus', 'Penaeus (Marsupenaeus) japonicus', true],
    ['Paracartia grani', 'Paracartia grani grani', true],
    ['Sepia pharaonis', 'Acanthosepion pharaonis', false],
    ['Saccostrea cucullata', 'Saccostrea cuccullata', false],
]);

it('proposes an accepted name only for a real change of name', function () {
    $worms = app(WormsService::class);

    expect($worms->acceptedNameFor(['AphiaID' => 181376, 'scientificname' => 'Sepia pharaonis', 'valid_AphiaID' => 1624463, 'valid_name' => 'Acanthosepion pharaonis']))
        ->toBe('Acanthosepion pharaonis')
        ->and($worms->acceptedNameFor(['AphiaID' => 210371, 'scientificname' => 'Penaeus japonicus', 'valid_AphiaID' => 1809214, 'valid_name' => 'Penaeus (Marsupenaeus) japonicus']))
        ->toBeNull()
        ->and(Catalogue_Status::fromWormsData('superseded combination', ['AphiaID' => 210371, 'scientificname' => 'Penaeus japonicus', 'valid_AphiaID' => 1809214, 'valid_name' => 'Penaeus (Marsupenaeus) japonicus']))
        ->toBe(Catalogue_Status::checked_accepted);
});

/**
 * WoRMS and EASIN as they answer when scoring the Sepia proposal: no
 * Mediterranean distribution under the new name, EASIN has no entry.
 */
function fakeScoringSources(): void
{
    Http::fake([
        '*/AphiaDistributionsByAphiaID/*' => Http::response(null, 204),
        'easin.jrc.ec.europa.eu/*' => Http::response(['Empty' => 'There are no results']),
    ]);
}

it('scores a genus change with the same author as safe, and a subjective synonym for expert review', function () {
    fakeScoringSources();
    $confidence = app(AcceptedNameConfidence::class);

    $genusChange = $confidence->assess(
        ['scientificname' => 'Sepia pharaonis', 'authority' => 'Ehrenberg, 1831', 'status' => 'superseded combination', 'family' => 'Sepiidae', 'rank' => 'Species'],
        ['AphiaID' => 1, 'scientificname' => 'Acanthosepion pharaonis', 'authority' => '(Ehrenberg, 1831)', 'family' => 'Sepiidae', 'rank' => 'Species'],
    );
    $synonym = $confidence->assess(
        ['scientificname' => 'Nanostrea fluctigera', 'authority' => '(Jousseaume, 1925)', 'status' => 'junior subjective synonym', 'family' => 'Ostreidae', 'rank' => 'Species'],
        ['AphiaID' => 2, 'scientificname' => 'Nanostrea pinnicola', 'authority' => '(Pagenstecher, 1877)', 'family' => 'Ostreidae', 'rank' => 'Species'],
    );
    $spelling = $confidence->assess(
        ['scientificname' => 'Saccostrea cucullata', 'authority' => '(Born, 1778)', 'status' => 'misspelling - incorrect subsequent spelling', 'family' => 'Ostreidae', 'rank' => 'Species'],
        ['AphiaID' => 3, 'scientificname' => 'Saccostrea cuccullata', 'authority' => '(Born, 1778)', 'family' => 'Ostreidae', 'rank' => 'Species'],
    );

    expect($genusChange['score'])->toBe(90)
        ->and(AcceptedNameConfidence::band($genusChange['score'])['label'])->toBe('Safe to move')
        ->and($synonym['score'])->toBe(55)
        ->and(AcceptedNameConfidence::band($synonym['score'])['label'])->toBe('Expert review')
        ->and($spelling['score'])->toBe(99);
});

it('counts a Mediterranean alien record under the new name as confirmation', function () {
    Http::fake([
        '*/AphiaDistributionsByAphiaID/*' => Http::response(null, 204),
        'easin.jrc.ec.europa.eu/*' => Http::response([['EASINID' => 'R08727', 'Name' => 'Loimia medusa', 'PresentInCountries' => [['Country' => 'IT'], ['Country' => 'UK']]]]),
    ]);

    $result = app(AcceptedNameConfidence::class)->assess(
        ['scientificname' => 'Axionice medusa', 'authority' => '(Savigny, 1822)', 'status' => 'superseded combination', 'family' => 'Terebellidae', 'rank' => 'Species'],
        ['AphiaID' => 131499, 'scientificname' => 'Loimia medusa', 'authority' => '(Savigny, 1822)', 'family' => 'Terebellidae', 'rank' => 'Species'],
    );

    expect($result['score'])->toBe(99)
        ->and(collect($result['reasons'])->pluck('label')->last())->toContain('EASIN: IT');
});

it('logs the move with its score and note, and undoes a rename', function () {
    fakeWormsSepia();
    $taxon = sepiaPharaonis();
    $taxon->update(['name_change_confidence' => 90, 'name_change_reasons' => [['label' => 'WoRMS: superseded combination', 'points' => 85]]]);
    $event = IntroEventRecord::factory()->create(['taxon_id' => $taxon->id]);
    $service = app(TaxonService::class);

    $service->moveToAcceptedName($taxon, 'Checked against Galil 2009.');

    $move = $service->lastUndoableMove($taxon->fresh());
    expect($move->properties['confidence'])->toBe(90)
        ->and($move->properties['note'])->toBe('Checked against Galil 2009.');

    $result = $service->undoLastMove($taxon->fresh());

    expect($result->scientificname)->toBe('Sepia pharaonis')
        ->and($result->aphia_id)->toBe(181376)
        ->and($result->proposed_accepted_name)->toBe('Acanthosepion pharaonis')
        ->and($event->fresh()->taxon_id)->toBe($taxon->id)
        ->and($service->lastUndoableMove($result))->toBeNull();
});

it('undoes a merge by restoring the old taxon and sending its records back', function () {
    fakeWormsSepia();
    $taxon = sepiaPharaonis();
    $accepted = Taxon::factory()->create(['scientificname' => 'Acanthosepion pharaonis', 'aphia_id' => 1624463]);
    $moved = IntroEventRecord::factory()->create(['taxon_id' => $taxon->id]);
    $own = IntroEventRecord::factory()->create(['taxon_id' => $accepted->id]);
    $service = app(TaxonService::class);

    $service->moveToAcceptedName($taxon);
    $service->undoLastMove($accepted->fresh());

    expect($taxon->fresh()->trashed())->toBeFalse()
        ->and($moved->fresh()->taxon_id)->toBe($taxon->id)
        ->and($own->fresh()->taxon_id)->toBe($accepted->id);
});

it('keeps the current name and does not propose the dismissed name again', function () {
    $taxon = sepiaPharaonis();
    app(TaxonService::class)->keepCurrentName($taxon, 'Follows the 2023 revision.');

    $record = ['AphiaID' => 181376, 'scientificname' => 'Sepia pharaonis', 'status' => 'superseded combination', 'valid_AphiaID' => 1624463, 'valid_name' => 'Acanthosepion pharaonis'];
    Http::fake(['*/AphiaSynonymsByAphiaID/*' => Http::response(null, 204)]);

    $again = $taxon->fresh();
    app(WormsService::class)->populateTaxonFromWorms($again, $record);
    expect($again->proposed_accepted_name)->toBeNull()
        ->and($again->dismissed_reason)->toBe('Follows the 2023 revision.');

    $other = $taxon->fresh();
    app(WormsService::class)->populateTaxonFromWorms($other, ['valid_name' => 'Doratosepion pharaonis', 'valid_AphiaID' => 99] + $record);
    expect($other->proposed_accepted_name)->toBe('Doratosepion pharaonis');
});

it('sends a proposal for expert review: assigns, asks in the discussion, notifies', function () {
    $taxon = sepiaPharaonis();
    $curator = User::factory()->create();
    $scientist = User::factory()->create();

    app(TaxonService::class)->sendForNameReview($taxon, $scientist, $curator, 'Levantine population?');

    expect($taxon->fresh()->name_reviewer_id)->toBe($scientist->id)
        ->and($taxon->comments()->first()->body)->toContain('Levantine population?')
        ->and($scientist->notifications()->count())->toBe(1);
});

it('moves only the selected species that are safe to move in bulk', function () {
    fakeWormsSepia();
    $safe = sepiaPharaonis();
    $safe->update(['name_change_confidence' => 90]);
    $risky = Taxon::factory()->create(['proposed_accepted_name' => 'Nanostrea pinnicola', 'name_change_confidence' => 55]);
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    livewire(ListTaxons::class, ['activeTab' => 'rename'])
        ->loadTable()
        ->callTableBulkAction('move_to_accepted_name', [$safe, $risky]);

    expect($safe->fresh()->scientificname)->toBe('Acanthosepion pharaonis')
        ->and($risky->fresh()->proposed_accepted_name)->toBe('Nanostrea pinnicola');
});

it('queues the monthly WoRMS check for every taxon except manual entries', function () {
    Queue::fake();
    $checked = Taxon::factory()->create();
    $manual = Taxon::factory()->create(['catalogue_status' => Catalogue_Status::manual_entry]);

    $this->artisan('taxa:check-worms-names')->assertSuccessful();

    Queue::assertPushed(FetchTaxaFromWormsJob::class, function (FetchTaxaFromWormsJob $job) use ($checked, $manual): bool {
        $ids = (fn () => $this->taxonIds)->call($job);

        return in_array($checked->id, $ids, true) && ! in_array($manual->id, $ids, true);
    });
});

it('tells curators on the panel that names need updating', function () {
    sepiaPharaonis();
    $admin = User::factory()->create()->assignRole('super_admin');

    $this->actingAs($admin)->get('/mamias')
        ->assertSee('New accepted names in WoRMS')
        ->assertSee('1 species has a new accepted name in WoRMS.');
});
