<?php

declare(strict_types=1);

use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Models\IntroEventRecord;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

it('clears needs_review when the record is saved', function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    $record = IntroEventRecord::factory()->create();
    $record->forceFill(['needs_review' => true])->save();

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect($record->refresh()->needs_review)->toBeFalse();
});
