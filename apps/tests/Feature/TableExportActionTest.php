<?php

use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Filament\Resources\NisSuggestions\Pages\ListNisSuggestions;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;

use function Pest\Livewire\livewire;

/**
 * Guards the export toolbar action wired into every list table.
 * If the action is dropped or the package's API changes, this fails.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('exposes the export action on every list table', function (string $page) {
    livewire($page)->assertTableActionExists('export');
})->with([
    ListTaxons::class,
    ListLiteratures::class,
    ListUsers::class,
    ListNisSuggestions::class,
    ListIntroEventRecords::class,
]);
