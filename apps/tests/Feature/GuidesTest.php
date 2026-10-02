<?php

use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

it('ships every screenshot a guide shows', function (string $guide) {
    preg_match_all('#\((/images/docs/[^)\s]+)\)#', file_get_contents(resource_path("docs/{$guide}.md")), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $image) {
        expect(public_path(ltrim($image, '/')))->toBeFile();
    }
})->with(['literatures', 'references', 'taxa', 'intro-events']);

it('opens the introduction events guide from the list', function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    expect(LiteratureGuide::html('intro-events'))->toContain('id="guide-check-pathways-against-easin"');

    livewire(ListIntroEventRecords::class)
        ->assertActionExists('guide')
        ->mountAction('guide')
        ->assertActionMounted('guide');
});
