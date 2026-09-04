<?php

use App\Enums\CoverageMethod;
use App\Enums\CoverageUnit;
use App\Enums\OccurrenceStatus;
use App\Filament\Forms\SinglePointMapPicker;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceForm;
use App\Livewire\MySpeciesReports;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\Taxon;
use App\Models\User;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

use function Pest\Livewire\livewire;

it('stores the coverage figure, its unit and how it was obtained', function () {
    $user = User::factory()->create();
    $introEventRecord = IntroEventRecord::factory()->create();

    $this->actingAs($user);

    livewire(MySpeciesReports::class)
        ->callAction('create', [
            'intro_event_record_id' => $introEventRecord->id,
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
            'coverage_value' => 12.5,
            'coverage_unit' => CoverageUnit::SQUARE_METRES->value,
            'coverage_method' => CoverageMethod::MEASURED->value,
        ])
        ->assertHasNoFormErrors();

    $occurrence = Occurrence::where('user_id', $user->id)->sole();

    expect($occurrence->coverage_value)->toBe(12.5)
        ->and($occurrence->coverage_unit)->toBe(CoverageUnit::SQUARE_METRES)
        ->and($occurrence->coverage_method)->toBe(CoverageMethod::MEASURED)
        ->and($occurrence->status)->toBe(OccurrenceStatus::PENDING);
});

it('rejects an extent figure with no method', function () {
    $user = User::factory()->create();
    $introEventRecord = IntroEventRecord::factory()->create();

    $this->actingAs($user);

    // The unit is not asserted here: picking a species fills it from the
    // kingdom, so only the method is still missing at this point.
    livewire(MySpeciesReports::class)
        ->callAction('create', [
            'intro_event_record_id' => $introEventRecord->id,
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
            'coverage_value' => 12.5,
        ])
        ->assertHasFormErrors(['coverage_method' => 'required_with']);

    expect(Occurrence::where('user_id', $user->id)->count())->toBe(0);
});

it('rejects an extent figure with no unit when no species carries a default', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(MySpeciesReports::class)
        ->callAction('create', [
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
            'coverage_value' => 12.5,
        ])
        ->assertHasFormErrors([
            'coverage_unit' => 'required_with',
            'coverage_method' => 'required_with',
        ]);

    expect(Occurrence::where('user_id', $user->id)->count())->toBe(0);
});

it('keeps a single point and writes it to the PostGIS column', function () {
    $user = User::factory()->create();
    $introEventRecord = IntroEventRecord::factory()->create();

    $this->actingAs($user);

    livewire(MySpeciesReports::class)
        ->callAction('create', [
            'intro_event_record_id' => $introEventRecord->id,
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
        ])
        ->assertHasNoFormErrors();

    $occurrence = Occurrence::where('user_id', $user->id)->sole();

    expect($occurrence->location)->toBe([['lat' => 36.5, 'lng' => 14.2]])
        ->and($occurrence->location_point->getLatitude())->toBe(36.5)
        ->and($occurrence->location_point->getLongitude())->toBe(14.2);
});

it('defaults the extent unit to m² for a plant and to individuals for an animal', function (string $kingdom, CoverageUnit $expected) {
    $introEventRecord = IntroEventRecord::factory()
        ->for(Taxon::factory()->state(['kingdom' => $kingdom]), 'taxon')
        ->create();

    $this->actingAs(User::factory()->create());

    livewire(MySpeciesReports::class)
        ->mountAction('create')
        ->setActionData(['intro_event_record_id' => $introEventRecord->id])
        ->assertActionDataSet(['coverage_unit' => $expected]);
})->with([
    ['Plantae', CoverageUnit::SQUARE_METRES],
    ['Chromista', CoverageUnit::SQUARE_METRES],
    ['Animalia', CoverageUnit::INDIVIDUALS],
]);

it('leaves an extent unit the reporter already picked alone', function () {
    $plant = IntroEventRecord::factory()
        ->for(Taxon::factory()->state(['kingdom' => 'Plantae']), 'taxon')
        ->create();
    $animal = IntroEventRecord::factory()
        ->for(Taxon::factory()->state(['kingdom' => 'Animalia']), 'taxon')
        ->create();

    $this->actingAs(User::factory()->create());

    livewire(MySpeciesReports::class)
        ->mountAction('create')
        ->setActionData(['coverage_unit' => CoverageUnit::SQUARE_METRES->value])
        ->setActionData(['intro_event_record_id' => $animal->id])
        ->assertActionDataSet(['coverage_unit' => CoverageUnit::SQUARE_METRES])
        ->setActionData(['intro_event_record_id' => $plant->id])
        ->assertActionDataSet(['coverage_unit' => CoverageUnit::SQUARE_METRES])
        ->assertHasNoActionErrors();
});

it('pins the detail tabs to one height without stretching the tab strip', function () {
    $tabs = collect(OccurrenceForm::getComponents())
        ->first(fn (mixed $component): bool => $component instanceof Tabs);

    // The floor belongs on the container. Filament merges a Tab's own
    // extraAttributes into its strip button as well as its panel, so putting a
    // height on the tabs themselves turns the strip into a tall column.
    expect($tabs->getExtraAttributes())->toBe(['style' => 'min-height: 34rem;'])
        ->and(collect($tabs->getDefaultChildComponents())->map(fn (Tab $tab): array => $tab->getExtraAttributes())->all())
        ->toBe([[], [], []]);
});

it('reduces any stored shape to one point for the map picker', function () {
    expect(SinglePointMapPicker::toSinglePoint([['lat' => 36.5, 'lng' => 14.2], ['lat' => 40.0, 'lng' => 20.0]]))
        ->toBe(['lat' => 36.5, 'lng' => 14.2])
        ->and(SinglePointMapPicker::toSinglePoint(['lat' => '36.5', 'lng' => '14.2']))
        ->toBe(['lat' => 36.5, 'lng' => 14.2])
        ->and(SinglePointMapPicker::toSinglePoint([]))->toBeNull()
        ->and(SinglePointMapPicker::toSinglePoint(null))->toBeNull();
});
