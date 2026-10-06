<?php

declare(strict_types=1);

use App\Filament\Resources\Literatures\Pages\EditLiterature;
use App\Models\Literature;
use App\Models\User;
use Filament\Facades\Filament;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
});

it('locks a record for the first editor and opens it read-only for the next', function () {
    $literature = Literature::factory()->create();

    $first = User::factory()->create()->assignRole('super_admin');
    $this->actingAs($first);
    livewire(EditLiterature::class, ['record' => $literature->getKey()])
        ->dispatch('resourceLockObserver::init')
        ->assertSet('isReadOnly', false);

    expect($literature->fresh()->isLocked())->toBeTrue();

    $second = User::factory()->create()->assignRole('super_admin');
    $this->actingAs($second);
    livewire(EditLiterature::class, ['record' => $literature->getKey()])
        ->dispatch('resourceLockObserver::init')
        ->assertSet('isReadOnly', true);
});

it('shows the authentication log to super_admin only', function () {
    $this->actingAs(User::factory()->create()->assignRole('scientist'))
        ->get(AuthenticationLogResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create()->assignRole('super_admin'))
        ->get(AuthenticationLogResource::getUrl('index'))
        ->assertOk();
});
