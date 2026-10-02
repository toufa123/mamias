<?php

declare(strict_types=1);

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordForm;
use App\Filament\Resources\Occurrences\OccurrenceResource;
use App\Filament\Resources\Occurrences\Pages\ListOccurrences;
use App\Filament\Widgets\OccurrencesMap;
use App\Models\Occurrence;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('sits under Introduction Events in the navigation, with pending occurrences as its badge', function () {
    Occurrence::factory()->pending()->count(2)->create();
    Occurrence::factory()->approved()->create();

    expect(OccurrenceResource::getNavigationParentItem())->toBe(IntroEventRecordResource::getNavigationLabel())
        ->and(OccurrenceResource::getNavigationBadge())->toBe('2');
});

it('lists occurrences and approves one from its own page', function () {
    $occurrence = Occurrence::factory()->pending()->create();

    livewire(ListOccurrences::class)
        ->assertCanSeeTableRecords([$occurrence])
        ->callAction(TestAction::make('approve')->table($occurrence));

    expect($occurrence->fresh()->status)->toBe(OccurrenceStatus::APPROVED);
});

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
