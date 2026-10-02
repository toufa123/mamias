<?php

declare(strict_types=1);

use App\Enums\EstablishmentStatus;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Models\CountryRecord;
use App\Models\IntroEventRecord;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('keeps the countries a species is present in apart from its first country', function () {
    $record = IntroEventRecord::factory()->create(['first_country' => ['Israel']]);
    CountryRecord::factory()->for($record, 'introEvent')->create(['country' => 'Israel', 'first_record_year' => 1990]);

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'countryRecords' => [
                ['country' => 'IL', 'establishment_status' => EstablishmentStatus::cases()[0]->value, 'first_record_year' => 1990],
                ['country' => 'TR', 'establishment_status' => null, 'first_record_year' => 2004],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $record->refresh();

    expect($record->first_country)->toBe(['Israel'])
        ->and($record->countryRecords()->orderBy('first_record_year')->pluck('country')->all())->toBe(['Israel', 'Türkiye']);
});

it('refuses the same country twice for one event', function () {
    $record = IntroEventRecord::factory()->create();

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'countryRecords' => [
                ['country' => 'IT', 'first_record_year' => 2001],
                ['country' => 'IT', 'first_record_year' => 2010],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors();

    expect($record->countryRecords()->count())->toBe(0);
});

it('filters events by a country they are present in', function () {
    $inItaly = CountryRecord::factory()->create(['country' => 'Italy'])->introEvent;
    $inGreece = CountryRecord::factory()->create(['country' => 'Greece'])->introEvent;

    livewire(ListIntroEventRecords::class)
        ->filterTable('present_country', ['Italy'])
        ->assertCanSeeTableRecords([$inItaly])
        ->assertCanNotSeeTableRecords([$inGreece]);
});

it('lists the countries on the event page', function () {
    $country = CountryRecord::factory()->create(['country' => 'Malta']);

    livewire(ListIntroEventRecords::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($country->introEvent))
        ->assertSchemaComponentExists('countryRecords', checkComponentUsing: fn ($component): bool => collect($component->getState())->pluck('country')->all() === ['Malta']);
});
