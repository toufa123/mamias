<?php

declare(strict_types=1);

use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Models\IntroEventRecord;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('saves an event whose first countries are stored as names', function () {
    $record = IntroEventRecord::factory()->create(['first_country' => ['Israel', 'Türkiye', 'Gaza strip']]);

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->assertSchemaStateSet(['first_country' => ['IL', 'TR', 'PS']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($record->refresh()->first_country)->toBe(['Israel', 'Türkiye', 'Gaza strip']);
});

it('stores a country picked in the form under its name', function () {
    $record = IntroEventRecord::factory()->create(['first_country' => ['Israel']]);

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->fillForm(['first_country' => ['SY', 'LB']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($record->refresh()->first_country)->toBe(['Syria', 'Lebanon']);
});

it('names a country the same way whatever spelling it arrives in', function (string $value, ?string $name) {
    expect(CountrySelectWithMedPriority::canonicalName($value))->toBe($name);
})->with([
    ['Israel', 'Israel'],
    ['IL', 'Israel'],
    ['Irael', 'Israel'],
    ['Turkey', 'Türkiye'],
    ['TR', 'Türkiye'],
    ['  Türkiye ', 'Türkiye'],
    ['Syrian Arab Republic', 'Syria'],
    ['Palestine, State of', 'Gaza strip'],
    ['Atlantis', null],
]);
