<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Filament\Resources\NisSuggestions\Pages\ListNisSuggestions;
use App\Filament\Resources\NisSuggestions\Pages\ViewNisSuggestion;
use App\Livewire\MySuggestions;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Models\User;
use App\Services\WormsService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

// --- Admin resource ---

it('renders the NIS suggestion list page', function () {
    livewire(ListNisSuggestions::class)->assertSuccessful();
});

it('shows pending suggestions in the table', function () {
    $suggestions = NisSuggestion::factory()->count(3)->pending()->create();
    livewire(ListNisSuggestions::class)->assertCanSeeTableRecords($suggestions);
});

it('renders the view page for a suggestion', function () {
    $suggestion = NisSuggestion::factory()->pending()->create();
    livewire(ViewNisSuggestion::class, ['record' => $suggestion->id])->assertSuccessful();
});

it('approves a suggestion and creates a taxon draft', function () {
    $uniqueName = 'Testus nistesticus '.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

    Taxon::where('scientificname', $uniqueName)->forceDelete();

    $suggestion = NisSuggestion::factory()->pending()->create([
        'suggested_scientific_name' => $uniqueName,
        'aphia_id' => 999991,
        'authority' => 'Test, 2026',
    ]);

    livewire(ListNisSuggestions::class)
        ->callAction(TestAction::make('approve')->table($suggestion), [
            'scientificname' => $uniqueName,
            'authority' => 'Test, 2026',
            'catalogue_status' => Catalogue_Status::not_checked->value,
            'notes' => '',
        ])
        ->assertNotified();

    assertDatabaseHas(NisSuggestion::class, [
        'id' => $suggestion->id,
        'status' => 'approved',
    ]);

    assertDatabaseHas(Taxon::class, [
        'scientificname' => $uniqueName,
        'catalogue_status' => Catalogue_Status::not_checked->value,
    ]);

    Taxon::where('scientificname', $uniqueName)->forceDelete();
    $suggestion->forceDelete();
});

it('rejects a suggestion with a reason', function () {
    $suggestion = NisSuggestion::factory()->pending()->create();

    livewire(ListNisSuggestions::class)
        ->callAction(TestAction::make('reject')->table($suggestion), [
            'rejection_reason' => 'Insufficient evidence.',
        ])
        ->assertNotified();

    assertDatabaseHas(NisSuggestion::class, [
        'id' => $suggestion->id,
        'status' => 'rejected',
        'rejection_reason' => 'Insufficient evidence.',
    ]);
});

it('approve action is hidden for already approved suggestions', function () {
    $suggestion = NisSuggestion::factory()->approved()->create();

    livewire(ListNisSuggestions::class)
        ->assertTableActionHidden('approve', $suggestion);
});

// --- Frontend Livewire page ---

it('renders the MySuggestions page for an authenticated user', function () {
    livewire(MySuggestions::class)->assertSuccessful();
});

it('shows only the current user suggestions in the table', function () {
    $mine = NisSuggestion::factory()->create(['user_id' => $this->user->id]);
    $other = NisSuggestion::factory()->create();

    livewire(MySuggestions::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});

it('blocks submission when scientific name already exists in the catalogue', function () {
    $uniqueName = 'Testus nistesticus '.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

    $existingTaxon = Taxon::factory()->create(['scientificname' => $uniqueName]);

    // Mock WoRMS so the Select's getOptionLabelUsing does not call the real API
    $aphiaId = 999998;
    $mockWorms = Mockery::mock(WormsService::class);
    $mockWorms->shouldReceive('getRecordByAphiaID')->with($aphiaId)->andReturn([
        'AphiaID' => $aphiaId,
        'scientificname' => $uniqueName,
        'authority' => 'Test, 2026',
        'status' => 'accepted',
    ]);
    $mockWorms->shouldReceive('searchSpecies')->andReturn([]);
    app()->instance(WormsService::class, $mockWorms);

    livewire(MySuggestions::class)
        ->callAction('create', [
            'aphia_id' => $aphiaId,
            'suggested_scientific_name' => $uniqueName,
            'authority' => 'Test, 2026',
            'worms_status' => 'accepted',
        ])
        ->assertHasActionErrors(['suggested_scientific_name' => 'unique']);

    $existingTaxon->forceDelete();
});
