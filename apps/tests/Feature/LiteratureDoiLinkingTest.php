<?php

declare(strict_types=1);

use App\Enums\LiteratureStatus;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Models\IntroEventRecord;
use App\Models\Literature;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Models\User;
use App\Notifications\NewLiteratureReferenceNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Http::preventStrayRequests();
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
});

function crossrefWork(array $overrides = []): array
{
    return ['message' => [
        'author' => [['family' => 'Zenetos', 'given' => 'A.'], ['family' => 'Galanidi', 'given' => 'M.']],
        'title' => ['Mediterranean non-indigenous species at the start of the 2020s'],
        'issued' => ['date-parts' => [[2020]]],
        'type' => 'journal-article',
        'URL' => 'https://doi.org/10.1007/s10530-020-02398-1',
        ...$overrides,
    ]];
}

it('stores DOIs normalized, so a pasted URL collides with the existing DOI', function () {
    Literature::factory()->create(['doi' => 'https://doi.org/10.1234/ABC']);

    expect(Literature::first()->doi)->toBe('10.1234/abc');

    Literature::factory()->create(['doi' => 'doi:10.1234/abc']);
})->throws(UniqueConstraintViolationException::class);

it('syncs year and retraction from Crossref without touching the curated reference', function () {
    Http::fake(['api.crossref.org/works/*' => Http::response(crossrefWork([
        'updated-by' => [['type' => 'retraction']],
    ]))]);

    $literature = Literature::factory()->create([
        'doi' => '10.1007/s10530-020-02398-1',
        'full_ref' => 'Curated reference text',
        'year' => null,
    ]);

    $this->artisan('literature:enrich')
        ->expectsOutputToContain('Newly retracted: '.$literature->code)
        ->assertSuccessful();

    $literature->refresh();

    expect($literature->year)->toBe(2020)
        ->and($literature->is_retracted)->toBeTrue()
        ->and($literature->full_ref)->toBe('Curated reference text')
        ->and($literature->crossref_checked_at)->not->toBeNull();

    // Fresh records are skipped on the next run.
    $this->artisan('literature:enrich')->expectsOutputToContain('Synced 0 DOI(s)');
});

it('suggests a DOI for a reference without one, and a curator can accept it', function () {
    Http::fake([
        'api.crossref.org/works?*' => Http::response(['message' => ['items' => [[
            'DOI' => '10.1007/S10530-020-02398-1',
            'title' => ['Mediterranean non-indigenous species at the start of the 2020s'],
        ]]]]),
        'api.crossref.org/works/*' => Http::response(crossrefWork()),
    ]);

    $literature = Literature::factory()->create([
        'doi' => null,
        'link' => null,
        'full_ref' => 'Zenetos, A. et al. (2020). Mediterranean non-indigenous species at the start of the 2020s: recent changes. Mar. Biodivers.',
    ]);

    $this->artisan('literature:enrich --search')->assertSuccessful();

    expect($literature->refresh()->suggested_doi)->toBe('10.1007/s10530-020-02398-1')
        ->and($literature->doi)->toBeNull();

    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('acceptSuggestedDoi')->table($literature))
        ->assertNotified('DOI accepted');

    $literature->refresh();

    expect($literature->doi)->toBe('10.1007/s10530-020-02398-1')
        ->and($literature->suggested_doi)->toBeNull()
        ->and($literature->year)->toBe(2020)
        ->and($literature->link)->toBe('https://doi.org/10.1007/s10530-020-02398-1');
});

it('links the WoRMS original description as an approved literature without notifying reviewers', function () {
    Notification::fake();

    Http::fake([
        'www.marinespecies.org/rest/AphiaSourcesByAphiaID/*' => Http::response([
            ['use' => 'additional source', 'reference' => 'Someone (2001). Not this one.', 'doi' => null, 'url' => null],
            ['use' => 'original description', 'reference' => 'Rüppell, E. (1835). Neue Wirbelthiere zu der Fauna von Abyssinien gehörig.', 'doi' => null, 'url' => 'https://www.marinespecies.org/aphia.php?p=sourcedetails&id=1'],
        ]),
    ]);

    $first = Taxon::factory()->create(['aphia_id' => 127160, 'authority' => '(Rüppell, 1835)']);
    $second = Taxon::factory()->create(['aphia_id' => 127161, 'authority' => '(Rüppell, 1835)']);

    $this->artisan('literature:enrich --descriptions')->assertSuccessful();

    $literature = $first->refresh()->originalDescription;

    expect($literature)->not->toBeNull()
        ->and($literature->status)->toBe(LiteratureStatus::APPROVED)
        ->and($literature->short_ref)->toBe('Rüppell, 1835')
        ->and($literature->year)->toBe(1835)
        ->and($second->refresh()->original_description_id)->toBe($literature->id)
        ->and(Literature::count())->toBe(1);

    Notification::assertNotSentTo(User::all(), NewLiteratureReferenceNotification::class);
});

it('lists approved references for a taxon with roles, oldest first', function () {
    $taxon = Taxon::factory()->create();

    $description = Literature::factory()->approved()->create(['year' => 1835, 'short_ref' => 'Rüppell, 1835']);
    $taxon->update(['original_description_id' => $description->id]);

    $firstRecord = Literature::factory()->approved()->create(['year' => 2004]);
    IntroEventRecord::factory()->create(['taxon_id' => $taxon->id, 'literature_id' => $firstRecord->id]);

    $supporting = Literature::factory()->approved()->create(['year' => 1990]);
    $suggestion = NisSuggestion::factory()->create(['taxon_id' => $taxon->id, 'status' => LiteratureStatus::APPROVED]);
    $suggestion->literatures()->attach($supporting);

    $pending = Literature::factory()->pending()->create(['year' => 2010]);
    IntroEventRecord::factory()->create(['taxon_id' => $taxon->id, 'literature_id' => $pending->id]);

    $references = $taxon->fresh()->literatureReferences();

    expect($references->pluck('literature.id')->all())->toBe([$description->id, $supporting->id, $firstRecord->id])
        ->and($references->pluck('role')->all())->toBe(['Original description', 'Supporting', 'First record']);
});

it('shows the references tab in the taxon view', function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    $taxon = Taxon::factory()->create();
    $literature = Literature::factory()->approved()->create([
        'short_ref' => 'Golani, 1998',
        'doi' => '10.5555/golani',
        'is_retracted' => true,
    ]);
    IntroEventRecord::factory()->create(['taxon_id' => $taxon->id, 'literature_id' => $literature->id]);

    // Action modals render as a Livewire partial, outside html(), so the
    // mounted infolist component is rendered directly.
    livewire(ListTaxons::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($taxon))
        ->assertSchemaComponentExists('literature_references', checkComponentUsing: function ($component): bool {
            $html = $component->toHtml();

            return str_contains($html, 'Golani, 1998')
                && str_contains($html, 'First record')
                && str_contains($html, 'Retracted')
                && str_contains($html, 'https://doi.org/10.5555/golani');
        });
});

it('builds a BibTeX entry, falling back to @misc without a DOI', function () {
    $literature = Literature::factory()->create([
        'doi' => null,
        'full_ref' => 'Title with {braces}',
        'year' => 1998,
        'link' => null,
        'short_ref' => 'Golani, 1998',
    ]);

    expect($literature->toBibtex())->toBe("@misc{{$literature->code},\n  title = {Title with braces},\n  year = {1998},\n  note = {Golani, 1998}\n}");

    Http::fake(['doi.org/*' => Http::response('@article{golani, title={X}}')]);
    $literature->update(['doi' => '10.5555/golani']);

    expect($literature->toBibtex())->toBe('@article{golani, title={X}}');
});
