<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\PathwayType;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Models\IntroEventRecord;
use App\Models\User;
use App\Services\IntroEventPathwayReconciler;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

/**
 * An event checked against EASIN $easinId, with MAMIAS pathways given as
 * subcategory codes and EASIN's as [name, Path_type] pairs.
 *
 * @param  list<string>  $mamias
 * @param  list<array{string, string}>  $easin
 */
function pathwayCheckedEvent(string $easinId, array $mamias, array $easin, string $country = 'Israel'): IntroEventRecord
{
    Http::fake(["easin.jrc.ec.europa.eu/apixg/catxg/easinid/{$easinId}" => Http::response([[
        'EASINID' => $easinId,
        'CBD_Pathways' => array_map(fn (array $pathway): array => ['Name' => $pathway[0], 'Path_type' => $pathway[1]], $easin),
    ]])]);

    $event = IntroEventRecord::factory()->create(['first_country' => [$country], 'pathway_check' => "MAMIAS vs EASIN {$easinId}"]);

    foreach ($mamias as $code) {
        $subcategory = CbdPathwaySubcategory::from($code);
        $event->pathwayRecords()->create(['category' => CbdPathwayCategory::from(explode('.', $code)[0]), 'subcategory' => $subcategory, 'pathway_type' => PathwayType::Primary]);
    }

    return $event->fresh('pathwayRecords');
}

it('decides each branch of the tree', function (string $easinId, array $mamias, array $easin, string $country, string $decision): void {
    $event = pathwayCheckedEvent($easinId, $mamias, $easin, $country);

    expect(app(IntroEventPathwayReconciler::class)->decide($event)['decision'])->toBe($decision);
})->with([
    'lowercase primary is read' => ['R90001', ['1.2'], [['RELEASE IN NATURE: Other intentional release', 'p']], 'Italy', 'same-categories'],
    'secondary pathways only' => ['R90002', ['3.1'], [['TRANSPORT - STOWAWAY: Angling/fishing equipment', 'S']], 'Italy', 'easin-no-primary'],
    'parasite of a Lessepsian host' => ['R90003', ['4.2'], [['CORRIDOR: Interconnected waterways/basins/seas', 'P']], 'Israel', 'parasite-host'],
    'Levantine stowaway gets a corridor' => ['R90004', ['3.1'], [['CORRIDOR: Interconnected waterways/basins/seas', 'P']], 'Egypt', 'add-corridor'],
    'Levantine corridor stands' => ['R90005', ['5.1'], [['TRANSPORT - STOWAWAY: Ship/boat hull fouling', 'P']], 'Israel', 'corridor-confirmed'],
    'corridor far from Suez' => ['R90006', ['5.1'], [['TRANSPORT - STOWAWAY: Ship/boat hull fouling', 'P']], 'Malta', 'conflict'],
    'aquarium release vs escape' => ['R90007', ['1.2'], [['ESCAPE FROM CONFINEMENT: Pet/aquarium/terrarium species', 'P']], 'Greece', 'same-vector'],
    'true conflict' => ['R90008', ['3.1'], [['RELEASE IN NATURE: Other intentional release', 'P']], 'Italy', 'conflict'],
]);

it('settles an agreement with a note and leaves conflicts alone', function (): void {
    $agreed = pathwayCheckedEvent('R90011', ['5.1'], [['UNAIDED: Natural dispersal', 'P']], 'Lebanon');
    $conflict = pathwayCheckedEvent('R90012', ['5.1'], [['UNAIDED: Natural dispersal', 'P']], 'Spain');

    app(IntroEventPathwayReconciler::class)->apply(IntroEventPathwayReconciler::AGREEMENTS);

    expect($agreed->fresh()->pathway_check)->toBeNull()
        ->and($agreed->fresh()->notes)->toContain('corridor-confirmed')
        // Kept for the Pathway checked tab, with the check it settled.
        ->and($agreed->fresh()->pathway_checked_at)->not->toBeNull()
        ->and($agreed->fresh()->pathway_resolution)->toMatchArray(['decision' => 'corridor-confirmed', 'easin_id' => 'R90011', 'check' => 'MAMIAS vs EASIN R90011'])
        ->and($conflict->fresh()->pathway_checked_at)->toBeNull()
        ->and($conflict->fresh()->pathway_check)->not->toBeNull();
});

it('moves a settled check from the Pathway check tab to Pathway checked', function (): void {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
    $event = pathwayCheckedEvent('R90013', ['5.1'], [['UNAIDED: Natural dispersal', 'P']], 'Lebanon');

    app(IntroEventPathwayReconciler::class)->settle($event, app(IntroEventPathwayReconciler::class)->decide($event));

    livewire(ListIntroEventRecords::class, ['activeTab' => 'pathway_check'])->assertCanNotSeeTableRecords([$event]);
    livewire(ListIntroEventRecords::class, ['activeTab' => 'pathway_checked'])
        ->assertCanSeeTableRecords([$event])
        ->assertSee('corridor-confirmed');
});
