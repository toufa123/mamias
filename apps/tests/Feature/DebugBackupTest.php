<?php

use App\Filament\Pages\BackupManager;
use App\Models\User;

use function Pest\Livewire\livewire;

it('can render backup manager page', function () {
    $user = User::role('super_admin')->first();
    if (! $user) {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
    }
    $this->actingAs($user);

    livewire(BackupManager::class)
        ->assertSuccessful();
});
