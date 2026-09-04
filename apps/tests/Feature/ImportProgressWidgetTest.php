<?php

declare(strict_types=1);

use App\Filament\Imports\TaxonImporter;
use App\Filament\Widgets\ImportProgressWidget;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function makeImport(User $user, array $attributes = []): Import
{
    return Import::create(array_merge([
        'user_id' => $user->id,
        'importer' => TaxonImporter::class,
        'file_name' => 'taxa.csv',
        'file_path' => 'imports/taxa.csv',
        'total_rows' => 100,
        'processed_rows' => 0,
        'successful_rows' => 0,
    ], $attributes));
}

test('import progress widget is hidden when the user has no recent import', function () {
    livewire(ImportProgressWidget::class)
        ->assertOk()
        ->assertDontSee('Importing taxa')
        ->assertDontSee('Import complete');
});

test('import progress widget shows a live progress bar while importing', function () {
    makeImport($this->user, ['processed_rows' => 40, 'successful_rows' => 40]);

    livewire(ImportProgressWidget::class)
        ->assertOk()
        ->assertSee('Importing taxa')
        ->assertSee('40%');
});

test('import progress widget shows the imported and failed summary when complete', function () {
    makeImport($this->user, [
        'total_rows' => 10,
        'processed_rows' => 10,
        'successful_rows' => 8,
        'completed_at' => now(),
    ]);

    livewire(ImportProgressWidget::class)
        ->assertOk()
        ->assertSee('Import complete')
        ->assertSee('imported')
        ->assertSee('failed');
});

test('a completed import announces itself so the list refreshes', function () {
    makeImport($this->user, [
        'total_rows' => 10,
        'processed_rows' => 10,
        'successful_rows' => 10,
        'completed_at' => now(),
    ]);

    livewire(ImportProgressWidget::class)
        ->assertDispatched('import-completed');
});

test('dismissing closes the modal and hides it afterwards', function () {
    makeImport($this->user, [
        'total_rows' => 10,
        'processed_rows' => 10,
        'successful_rows' => 10,
        'completed_at' => now(),
    ]);

    livewire(ImportProgressWidget::class)
        ->assertSee('Import complete')
        ->call('dismiss')
        ->assertDispatched('close-modal');

    // Dismissal is cached, so the modal no longer resolves an import to show.
    expect((new ImportProgressWidget)->getImport())->toBeNull();
});
