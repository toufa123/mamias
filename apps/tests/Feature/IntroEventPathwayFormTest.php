<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\PathwayType;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Models\IntroEventRecord;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('saves a pathway known only at CBD category level', function () {
    $record = IntroEventRecord::factory()->create();

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'pathwayRecords' => [
                ['pathway_type' => PathwayType::Primary->value, 'category' => CbdPathwayCategory::Corridor->value, 'subcategory' => null],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($record->pathwayRecords()->sole())
        ->category->toBe(CbdPathwayCategory::Corridor)
        ->subcategory->toBeNull();
});

it('requires the CBD category of a pathway', function () {
    $record = IntroEventRecord::factory()->create();

    livewire(EditIntroEventRecord::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'pathwayRecords' => [
                ['pathway_type' => PathwayType::Primary->value, 'category' => null],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors();

    expect($record->pathwayRecords()->count())->toBe(0);
});
