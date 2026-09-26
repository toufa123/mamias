<?php

declare(strict_types=1);

use App\Enums\LiteratureType;
use App\Livewire\MyReferences;
use App\Models\Literature;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('user', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    $this->actingAs($this->user);
});

it('renders for authenticated users', function () {
    livewire(MyReferences::class)->assertSuccessful();
});

it('creates a pending reference on submit', function () {
    $uniqueRef = 'Smith, J. (2025). Test reference '.uniqid().'.';

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', 'Smith et al., 2025')
        ->set('mountedActions.0.data.full_ref', $uniqueRef)
        ->set('mountedActions.0.data.type', LiteratureType::ARTICLE->value)
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified();

    assertDatabaseHas(Literature::class, [
        'short_ref' => 'Smith et al., 2025',
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);
});

it('validates required fields', function () {
    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', '')
        ->set('mountedActions.0.data.full_ref', '')
        ->set('mountedActions.0.data.type', '')
        ->callMountedAction()
        ->assertHasActionErrors([
            'short_ref' => 'required',
            'full_ref' => 'required',
            'type' => 'required',
        ]);
});

it('shows only the current user references', function () {
    $ownRef = Literature::factory()->create(['created_by' => $this->user->id]);
    $otherUser = User::factory()->create();
    $otherRef = Literature::factory()->create(['created_by' => $otherUser->id]);

    livewire(MyReferences::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$ownRef])
        ->assertCanNotSeeTableRecords([$otherRef]);
});

it('allows uploading a PDF file', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', 'File Test')
        ->set('mountedActions.0.data.full_ref', 'Full reference for file test')
        ->set('mountedActions.0.data.type', LiteratureType::ARTICLE->value)
        ->set('mountedActions.0.data.file_path', $file)
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $literature = Literature::where('short_ref', 'File Test')->first();
    expect($literature->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($literature->file_path);
});

it('can view a reference', function () {
    $reference = Literature::factory()->create(['created_by' => $this->user->id]);

    livewire(MyReferences::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make('view')->table($reference))
        ->mountAction(TestAction::make('view')->table($reference))
        ->assertActionMounted(TestAction::make('view')->table($reference))
        ->assertHasNoActionErrors();
});

it('hides pdf field in view mode when empty', function () {
    $reference = Literature::factory()->create([
        'created_by' => $this->user->id,
        'file_path' => null,
    ]);

    livewire(MyReferences::class)
        ->loadTable()
        ->mountTableAction('view', $reference)
        ->assertFormFieldHidden('file_path');
});

it('shows pdf field in view mode when not empty', function () {
    $reference = Literature::factory()->create([
        'created_by' => $this->user->id,
        'file_path' => 'test.pdf',
    ]);

    livewire(MyReferences::class)
        ->loadTable()
        ->mountTableAction('view', $reference)
        ->assertFormFieldVisible('file_path');
});

it('shows pdf field in create mode', function () {
    livewire(MyReferences::class)
        ->mountAction('create')
        ->assertFormFieldVisible('file_path');
});

it('offers no metadata fetch in the read-only view', function () {
    $reference = Literature::factory()->create(['created_by' => $this->user->id, 'doi' => '10.1000/view-only']);

    livewire(MyReferences::class)
        ->loadTable()
        ->mountTableAction('view', $reference)
        ->assertMountedActionModalSeeHtml('Open DOI in new tab')
        ->assertMountedActionModalDontSeeHtml('Fetch metadata');
});

it('opens the contributor guide in a popup', function () {
    livewire(MyReferences::class)
        ->mountAction('guide')
        ->assertMountedActionModalSee('Step 1: the DOI')
        ->assertMountedActionModalSeeHtml('/images/docs/references/02-doi-step.png');
});

it('flags discussion comments until the other side replies', function () {
    $moderator = User::factory()->create();
    $reference = Literature::factory()->create(['created_by' => $this->user->id]);

    $unansweredBy = fn (bool $fromSubmitter) => Literature::withUnansweredComments($fromSubmitter)->pluck('id')->all();

    $this->travel(1)->minutes();
    $reference->comment('Question from the submitter', $this->user);
    expect($unansweredBy(true))->toBe([$reference->id])
        ->and($unansweredBy(false))->toBe([]);

    $this->travel(1)->minutes();
    $reference->comment('Answer from a moderator', $moderator);
    expect($unansweredBy(true))->toBe([])
        ->and($unansweredBy(false))->toBe([$reference->id]);
});

it('filters my references by year', function () {
    $recent = Literature::factory()->create(['year' => 2014, 'created_by' => $this->user->id]);
    $older = Literature::factory()->create(['year' => 2009, 'created_by' => $this->user->id]);

    livewire(MyReferences::class)
        ->filterTable('year', 2014)
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$older]);
});
