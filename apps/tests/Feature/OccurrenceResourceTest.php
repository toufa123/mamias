<?php

declare(strict_types=1);

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordForm;
use App\Filament\Resources\Occurrences\OccurrenceResource;
use App\Filament\Resources\Occurrences\Pages\ListOccurrences;
use App\Filament\Widgets\OccurrencesMap;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Spatie\Permission\Models\Permission;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('is a top-level item with its own icon, right after Introduction Events, with pending occurrences as its badge', function () {
    Occurrence::factory()->pending()->count(2)->create();
    Occurrence::factory()->approved()->create();

    // Not a sub-item: Filament hides those while their parent is closed.
    expect(OccurrenceResource::getNavigationParentItem())->toBeNull()
        ->and(OccurrenceResource::getNavigationIcon())->toBe('tabler-binoculars')
        ->and(OccurrenceResource::getNavigationGroup())->toBe(IntroEventRecordResource::getNavigationGroup())
        ->and(OccurrenceResource::getNavigationSort())->toBe(IntroEventRecordResource::getNavigationSort())
        ->and(OccurrenceResource::getNavigationBadge())->toBe('2');
});

it('lists occurrences and approves one from its own page', function () {
    $occurrence = Occurrence::factory()->pending()->create();

    livewire(ListOccurrences::class)
        ->assertCanSeeTableRecords([$occurrence])
        ->callAction(TestAction::make('approve')->table($occurrence));

    expect($occurrence->fresh()->status)->toBe(OccurrenceStatus::APPROVED);
});

it('lets staff add an occurrence that skips the review queue, and keeps public users out', function (string $role) {
    // Scientists reach the resource through their Shield permission, which RefreshDatabase truncates.
    Permission::findOrCreate('ViewAny:IntroEventRecord');
    $staff = User::factory()->create()->assignRole($role)->givePermissionTo('ViewAny:IntroEventRecord');
    $this->actingAs($staff);

    livewire(ListOccurrences::class)
        ->callAction('create', [
            'intro_event_record_id' => IntroEventRecord::factory()->create()->id,
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
        ])
        ->assertHasNoFormErrors()
        ->assertDispatched('occurrence-moderated');

    expect(Occurrence::where('user_id', $staff->id)->sole()->status)->toBe(OccurrenceStatus::APPROVED);

    $this->actingAs(User::factory()->create()->assignRole('user'));
    expect(OccurrenceResource::canCreate())->toBeFalse();
})->with(['super_admin', 'scientist']);

it('no longer shows occurrences inside an introduction event', function () {
    expect(IntroEventRecordResource::getRelations())->toBe([])
        ->and(method_exists(IntroEventRecordForm::class, 'occurrenceSchema'))->toBeFalse();
});

it('maps every occurrence the table shows, whatever its status', function () {
    Occurrence::factory()->approved()->create(['observed_at' => '2024-05-01']);
    Occurrence::factory()->pending()->create(['observed_at' => '2023-01-01']);
    Occurrence::factory()->rejected()->create(['observed_at' => '2022-02-02']);

    livewire(ListOccurrences::class)->assertSeeLivewire(OccurrencesMap::class);

    livewire(OccurrencesMap::class)
        ->assertSee('2024-05-01')
        ->assertSee('2023-01-01')
        ->assertSee('2022-02-02');
});

it('opens a clicked pin in the view modal and rejects it there with a reason', function () {
    $occurrence = Occurrence::factory()->pending()->create();

    livewire(OccurrencesMap::class)
        ->call('handleLayerClick', "occurrence-{$occurrence->id}")
        ->assertDispatchedTo(ListOccurrences::class, 'open-occurrence', id: $occurrence->id);

    livewire(ListOccurrences::class)
        ->call('openOccurrence', $occurrence->id)
        ->assertActionMounted(TestAction::make('view')->table($occurrence));

    livewire(ListOccurrences::class)
        ->callAction(
            [TestAction::make('view')->table($occurrence), TestAction::make('reject')],
            ['moderation_notes' => 'Photo shows a native lookalike.'],
        )
        ->assertHasNoFormErrors()
        ->assertDispatched('occurrence-moderated');

    expect($occurrence->fresh())
        ->status->toBe(OccurrenceStatus::REJECTED)
        ->moderation_notes->toBe('Photo shows a native lookalike.');
});

it('keeps a persistent pending-review toast on panel pages for moderators, linking to the pending list', function () {
    Occurrence::factory()->pending()->count(2)->create();

    $this->get('/mamias')
        ->assertSee('Pending Occurrences')
        ->assertSee('There are 2 species occurrences pending review.')
        ->assertSee('occurrences?filters%5Bstatus%5D%5Bvalue%5D=pending', escape: false);
});

it('shows no pending-review toast once nothing is pending', function () {
    Occurrence::factory()->approved()->create();

    $this->get('/mamias')->assertDontSee('Pending Occurrences');
});
