<?php

declare(strict_types=1);

use App\Enums\LiteratureStatus;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Models\Literature;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

it('renders the literature list page with tabs', function () {
    livewire(ListLiteratures::class)->assertSuccessful();
});

it('shows pending references in the pending tab', function () {
    $pending = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$pending]);
});

it('approves a pending reference', function () {
    $literature = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('approve')->table($literature));

    expect($literature->fresh()->status)->toBe(LiteratureStatus::APPROVED);
});

it('rejects a pending reference', function () {
    $literature = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('reject')->table($literature));

    expect($literature->fresh()->status)->toBe(LiteratureStatus::REJECTED);
});

it('hides approve and reject actions for approved references', function () {
    $literature = Literature::factory()->create(['status' => LiteratureStatus::APPROVED]);

    livewire(ListLiteratures::class)
        ->assertActionHidden(TestAction::make('approve')->table($literature))
        ->assertActionHidden(TestAction::make('reject')->table($literature));
});
