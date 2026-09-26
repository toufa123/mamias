<?php

declare(strict_types=1);

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Filament\Resources\Literatures\Pages\EditLiterature;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use App\Livewire\MyReferences;
use App\Models\IntroEventRecord;
use App\Models\Literature;
use App\Models\NisSuggestion;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Kirschbaum\Commentions\Actions\SaveComment;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('user', 'web');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

it('approves several references at once, skipping those already approved', function () {
    $pending = Literature::factory()->pending()->count(2)->create();
    $approved = Literature::factory()->approved()->create(['reviewed_at' => now()->subYear()]);

    livewire(ListLiteratures::class)
        ->set('activeTab', 'all')
        ->callTableBulkAction('bulkApprove', [...$pending, $approved])
        ->assertNotified('2 references approved');

    expect($pending->every(fn (Literature $l) => $l->fresh()->status === LiteratureStatus::APPROVED))->toBeTrue()
        ->and($approved->fresh()->reviewed_at->isToday())->toBeFalse();
});

it('requires a reason to reject several references', function () {
    $pending = Literature::factory()->pending()->count(2)->create();

    livewire(ListLiteratures::class)
        ->callTableBulkAction('bulkReject', $pending, data: ['review_comment' => ''])
        ->assertHasFormErrors(['review_comment' => 'required']);
});

it('opens the next pending reference after "Approve & next"', function () {
    $first = Literature::factory()->pending()->create(['created_at' => now()->subDay()]);
    $second = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->mountAction(TestAction::make('approve')->table($first))
        ->callMountedAction(['next' => true])
        ->assertSet('mountedActions.0.name', 'approve')
        ->assertSet('mountedActions.0.context.recordKey', $second->getKey());

    expect($first->fresh()->status)->toBe(LiteratureStatus::APPROVED);
});

it('opens the pending tab for reviewers when there is work', function () {
    Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)->assertSet('activeTab', 'pending');
});

it('keeps cited references when bulk deleting', function () {
    $cited = IntroEventRecord::factory()->create()->literature;
    $unused = Literature::factory()->approved()->create();

    livewire(ListLiteratures::class)
        ->set('activeTab', 'all')
        ->callTableBulkAction('delete', [$cited, $unused]);

    assertModelExists($cited);
    assertModelMissing($unused);
});

it('hides delete on a cited reference', function () {
    $cited = IntroEventRecord::factory()->create()->literature;

    livewire(EditLiterature::class, ['record' => $cited->getKey()])
        ->assertActionHidden('delete')
        ->assertActionVisible('merge');
});

it('merges a duplicate, moving its citations to the kept reference', function () {
    $keep = Literature::factory()->approved()->create();
    $duplicate = Literature::factory()->approved()->create();
    $event = IntroEventRecord::factory()->create(['literature_id' => $duplicate->id]);
    $suggestion = NisSuggestion::factory()->create();
    $duplicate->nisSuggestions()->attach($suggestion);

    livewire(EditLiterature::class, ['record' => $duplicate->getKey()])
        ->callAction('merge', data: ['target_id' => $keep->id])
        ->assertRedirect();

    assertModelMissing($duplicate);
    expect($event->fresh()->literature_id)->toBe($keep->id)
        ->and($keep->nisSuggestions()->whereKey($suggestion->id)->exists())->toBeTrue();
});

it('finds near-duplicate references', function () {
    $existing = Literature::factory()->create([
        'full_ref' => 'Zenetos, A. (2010). Alien species in the Mediterranean Sea by 2010. Mediterranean Marine Science, 11(2), 381-493.',
    ]);

    expect(Literature::similarTo('Zenetos A 2010 Alien species in the Mediterranean sea by 2010 Mediterranean Marine Science 11(2) 381-493')->first()?->is($existing))->toBeTrue()
        ->and(Literature::similarTo('Completely unrelated paper about coral reefs in the Pacific')->exists())->toBeFalse();
});

it('tells a public submitter the DOI is already in MAMIAS', function () {
    $existing = Literature::factory()->approved()->create(['doi' => '10.1000/existing']);
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user);

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.doi', '10.1000/existing')
        ->assertMountedActionModalSee("Already in MAMIAS as {$existing->code}");
});

it('submits a reference through the DOI-first wizard without a DOI', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user);

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', 'Doe, 2026')
        ->set('mountedActions.0.data.full_ref', 'Doe, J. (2026). A reference typed by hand '.uniqid().'.')
        ->set('mountedActions.0.data.type', LiteratureType::ARTICLE->value)
        ->callMountedAction()
        ->assertHasNoActionErrors();

    assertDatabaseHas(Literature::class, ['short_ref' => 'Doe, 2026', 'status' => 'pending', 'created_by' => $user->id]);
});

it('flags rejected references and unanswered replies to the submitter', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $rejected = Literature::factory()->rejected()->create(['created_by' => $user->id]);

    SaveComment::run($rejected, $user, 'Which DOI should I use?');
    $this->travel(1)->minutes();
    SaveComment::run($rejected, $this->admin, 'The publisher one.');

    $this->actingAs($user);

    livewire(MyReferences::class)
        ->assertSee('1 reference was not accepted.')
        ->assertSee('A reviewer commented on 1 reference.')
        ->assertTableColumnStateSet('comments_count', 2, $rejected);
});

it('shows the submitter the code, type and year, and the reviewer by role', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $reference = Literature::factory()->rejected()->create([
        'created_by' => $user->id,
        'year' => 2019,
        'review_comment' => 'Wrong year.',
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => now(),
    ]);

    $this->actingAs($user);

    livewire(MyReferences::class)
        ->loadTable()
        ->assertTableColumnVisible('code')
        ->assertTableColumnVisible('type')
        ->assertTableColumnStateSet('year', 2019, $reference)
        ->mountTableAction('showReviewComment', $reference)
        ->assertMountedActionModalSee('Administrator')
        ->assertMountedActionModalDontSee($this->admin->name);
});

it('opens the literature guide in a popup from the list', function () {
    livewire(ListLiteratures::class)
        ->mountAction('guide')
        ->assertMountedActionModalSee('Review pending references')
        ->assertMountedActionModalSeeHtml('href="#guide-merge-a-duplicate"');
});

it('puts each BibTeX field from Crossref on its own line', function () {
    expect(LiteraturesTable::formatBibtex(' @article{Galil_2009, title={Taking stock}, volume={11}, year={2009} }'))
        ->toBe("@article{Galil_2009,\n  title = {Taking stock},\n  volume = {11},\n  year = {2009}\n}");
});

it('shows a suggested DOI in the empty DOI cell', function () {
    $literature = Literature::factory()->pending()->create(['doi' => null, 'suggested_doi' => '10.12681/mms.327']);

    livewire(ListLiteratures::class)
        ->loadTable()
        ->assertSee('Suggested: 10.12681/mms.327');
});
