<?php

declare(strict_types=1);

use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

/** The HTML of one component of the mounted view modal. */
function viewModalHtml(Taxon $taxon, string $key): string
{
    $html = '';

    // Action modals render as a Livewire partial, outside html(), so the
    // mounted infolist component is rendered directly.
    livewire(ListTaxons::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($taxon))
        ->assertSchemaComponentExists($key, checkComponentUsing: function ($component) use (&$html): bool {
            $html = $component->toHtml();

            return true;
        });

    return $html;
}

it('shows WoRMS synonyms as a table linking to WoRMS', function () {
    $taxon = Taxon::factory()->create(['synonyms_data' => [
        ['AphiaID' => 400781, 'scientificname' => 'Ablenes hians', 'authority' => '(Valenciennes, 1846)', 'status' => 'unaccepted', 'unacceptreason' => 'misspelling'],
    ]]);

    expect(viewModalHtml($taxon, 'synonyms'))
        ->toContain('<em>Ablenes hians</em>', 'marinespecies.org', '400781', 'misspelling');
});

it('warns only when WoRMS proposes another accepted name', function () {
    $moved = Taxon::factory()->create(['proposed_accepted_name' => 'Nanostrea fluctigera', 'unacceptreason' => 'synonym']);
    $accepted = Taxon::factory()->create(['proposed_accepted_name' => null, 'unacceptreason' => null]);

    livewire(ListTaxons::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($moved))
        ->assertSchemaComponentExists('name_warning', checkComponentUsing: fn ($component): bool => $component->isVisible()
            && str_contains($component->toHtml(), 'WoRMS proposes another accepted name')
            && str_contains($component->toHtml(), 'Reason: synonym'));

    livewire(ListTaxons::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($accepted))
        ->assertSchemaComponentExists('name_warning', checkComponentUsing: fn ($component): bool => $component->isHidden());
});
