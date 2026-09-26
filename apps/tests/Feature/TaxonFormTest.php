<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Filament\Resources\Taxons\Pages\CreateTaxon;
use App\Filament\Resources\Taxons\Pages\EditTaxon;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('scientist', 'web');
    Role::findOrCreate('user', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

// List page

it('renders the taxon list page for an authorized user', function () {
    livewire(ListTaxons::class)->assertSuccessful();
});

it('shows taxon records in the table', function () {
    $taxons = Taxon::factory()->count(3)->create();
    livewire(ListTaxons::class)->loadTable()->assertCanSeeTableRecords($taxons);
});

it('shows no records when the table is empty', function () {
    livewire(ListTaxons::class)->loadTable()->assertCountTableRecords(0);
});

// Create form

it('requires a scientific name on create', function () {
    livewire(CreateTaxon::class)
        ->fillForm(['scientificname' => null])
        ->call('create')
        ->assertHasFormErrors(['scientificname' => 'required']);
});

it('creates a taxon with a scientific name', function () {
    livewire(CreateTaxon::class)
        ->fillForm([
            'scientificname' => 'Caulerpa cylindracea',
            'authority' => 'Sonder',
            'kingdom' => 'Plantae',
            'rank' => 'Species',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('taxas', [
        'scientificname' => 'Caulerpa cylindracea',
        'authority' => 'Sonder',
    ]);
});

// Edit form

it('shows existing taxon data on the edit form', function () {
    $taxon = Taxon::factory()->create([
        'scientificname' => 'Caulerpa taxifolia',
        'authority' => '(M.Vahl) C.Agardh',
        'kingdom' => 'Plantae',
    ]);

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->assertFormSet([
            'scientificname' => 'Caulerpa taxifolia',
            'authority' => '(M.Vahl) C.Agardh',
            'kingdom' => 'Plantae',
        ]);
});

it('saves updated taxon fields on edit', function () {
    $taxon = Taxon::factory()->create([
        'scientificname' => 'Caulerpa taxifolia',
        'notes' => null,
    ]);

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->fillForm(['notes' => 'Mediterranean invasive species.'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('taxas', [
        'id' => $taxon->id,
        'notes' => 'Mediterranean invasive species.',
    ]);
});

it('saves updated authority on edit', function () {
    $taxon = Taxon::factory()->create(['authority' => 'Old Author']);

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->fillForm(['authority' => 'New Author'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($taxon->fresh()->authority)->toBe('New Author');
});

it('saves updated catalogue_status on edit', function () {
    $taxon = Taxon::factory()->create();

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->fillForm(['catalogue_status' => Catalogue_Status::checked_accepted->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($taxon->fresh()->catalogue_status)->toBe(Catalogue_Status::checked_accepted);
});

// Delete action

it('deletes a taxon via the delete header action', function () {
    $taxon = Taxon::factory()->create();

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('taxas', ['id' => $taxon->id]);
});

// Duplicate handling on a not-checked-yet record

it('offers to delete a not-checked taxon instead of saving a duplicate name', function () {
    // The typed name is unique, but the normalizer rewrites "Guttulina sp." to
    // "Guttulina" on save — which the unique rule on the field never sees.
    Taxon::factory()->create(['scientificname' => 'Guttulina']);
    $duplicate = Taxon::withoutEvents(fn () => Taxon::factory()->create([
        'scientificname' => 'Guttulina sp.',
        'catalogue_status' => Catalogue_Status::not_checked,
    ]));

    livewire(EditTaxon::class, ['record' => $duplicate->id])
        ->fillForm(['notes' => 'Reviewing'])
        ->call('save')
        ->assertNotified('That species is already in the database');

    expect($duplicate->fresh()->scientificname)->toBe('Guttulina sp.');
});

it('deletes the duplicate when the curator takes the offered action', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);
    $duplicate = Taxon::factory()->create(['catalogue_status' => Catalogue_Status::not_checked]);

    livewire(EditTaxon::class, ['record' => $duplicate->id])
        ->call('deleteDuplicateTaxon');

    $this->assertSoftDeleted('taxas', ['id' => $duplicate->id]);
});

it('lets a checked taxon save without the duplicate offer', function () {
    $taxon = Taxon::factory()->create([
        'scientificname' => 'Percnon gibbesi',
        'catalogue_status' => Catalogue_Status::checked_accepted,
    ]);

    livewire(EditTaxon::class, ['record' => $taxon->id])
        ->fillForm(['notes' => 'Reviewed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($taxon->fresh()->notes)->toBe('Reviewed');
});
