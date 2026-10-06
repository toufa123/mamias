<?php

use App\Filament\Pages\AdminManual;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Filament\Resources\Occurrences\Pages\ListOccurrences;
use App\Livewire\MySpeciesReports;
use App\Livewire\MySuggestions;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

it('ships every screenshot a guide shows', function (string $guide) {
    preg_match_all('#\((/images/docs/[^)\s]+)\)#', file_get_contents(resource_path("docs/{$guide}.md")), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $image) {
        expect(public_path(ltrim($image, '/')))->toBeFile();
    }
})->with(['literatures', 'references', 'taxa', 'intro-events', 'occurrences', 'species-reports', 'suggestions']);

it('opens the introduction events guide from the list', function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    expect(LiteratureGuide::html('intro-events'))->toContain('id="guide-check-pathways-against-easin"');

    livewire(ListIntroEventRecords::class)
        ->assertActionExists('guide')
        ->mountAction('guide')
        ->assertActionMounted('guide');
});

it('opens the occurrences guide from the list', function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    expect(LiteratureGuide::html('occurrences'))->toContain('id="guide-review-a-report"');

    livewire(ListOccurrences::class)
        ->assertActionExists('guide')
        ->mountAction('guide')
        ->assertActionMounted('guide');
});

it('offers a guide to contributors on their public pages', function (string $component, string $guide, string $anchor) {
    $this->actingAs(User::factory()->create()->assignRole('user'));

    livewire($component)
        ->mountAction(TestAction::make('guide')->table())
        ->assertActionMounted(TestAction::make('guide')->table());

    expect(LiteratureGuide::html($guide))->toContain($anchor);
})->with([
    'species reports' => [MySpeciesReports::class, 'species-reports', 'id="guide-report-a-sighting"'],
    'suggestions' => [MySuggestions::class, 'suggestions', 'id="guide-suggest-a-species"'],
]);

it('serves the user manual to anonymous visitors and links it from the site', function () {
    $this->get(route('manual'))
        ->assertOk()
        ->assertSee('Browsing without an account')
        ->assertSee('href="'.route('manual').'"', escape: false);
});

it('shows the admin manual in the panel to staff only', function (string $role, int $status) {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));
    $this->actingAs(User::factory()->create()->assignRole($role));

    $response = $this->get(AdminManual::getUrl());

    $response->assertStatus($status);

    if ($status === 200) {
        $response->assertSee('Review queues')->assertSee(route('manual'), escape: false);
    }
})->with([
    'super_admin' => ['super_admin', 200],
    'scientist' => ['scientist', 200],
    'user' => ['user', 403],
]);

it('links only to headings the manuals have', function (string $manual) {
    $html = LiteratureGuide::html($manual);
    preg_match_all('/href="#(guide-[^"]+)"/', $html, $links);

    foreach ($links[1] as $id) {
        expect($html)->toContain("id=\"{$id}\"");
    }
})->with(['admin-manual', 'user-manual']);

it('downloads the user manual as a PDF, for anyone', function () {
    Storage::fake();

    $this->get(route('manual.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('MAMIAS-User-Manual.pdf');

    // Built once, then served from storage until the manual changes.
    expect(Storage::files('manuals'))->toHaveCount(1);
    $this->get(route('manual.pdf'))->assertOk();
    expect(Storage::files('manuals'))->toHaveCount(1);
});

it('downloads the admin manual as a PDF, for staff only', function () {
    Storage::fake();

    $this->get(route('admin-manual.pdf'))->assertRedirect();

    $this->actingAs(User::factory()->create()->assignRole('user'))
        ->get(route('admin-manual.pdf'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create()->assignRole('scientist'))
        ->get(route('admin-manual.pdf'))
        ->assertOk()
        ->assertDownload('MAMIAS-Administration-Manual.pdf');
});
