<?php

declare(strict_types=1);

use App\Filament\Imports\TaxonImporter;
use App\Filament\Widgets\ImportProgressWidget;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // The suite runs against the shared Redis cache (the container's CACHE_STORE
    // wins over phpunit.xml), so a dismissal cached by an earlier run can hide
    // the modal from a later test that happens to reuse the same user id.
    Cache::forget('taxon-import-dismissed-'.$this->user->id);
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

test('import progress widget separates skipped duplicates and offers the Excel download', function () {
    $import = makeImport($this->user, [
        'total_rows' => 10,
        'processed_rows' => 10,
        'successful_rows' => 7,
        'completed_at' => now(),
    ]);

    $import->failedRows()->createMany([
        ['data' => ['Scientific Name' => 'Caulerpa cylindracea'], 'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE],
        ['data' => ['Scientific Name' => 'Percnon gibbesi'], 'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE],
        ['data' => ['Scientific Name' => ''], 'validation_error' => 'The Scientific Name field is required.'],
    ]);

    livewire(ImportProgressWidget::class)
        ->assertOk()
        ->assertSee('Already in database')
        ->assertSee('not imported because the scientific name is')
        ->assertSee('Download not imported species')
        ->assertSee(route('imports.not-imported-rows.download', ['import' => $import, 'format' => 'xlsx']), escape: false)
        ->assertSee(route('imports.not-imported-rows.download', ['import' => $import, 'format' => 'csv']), escape: false);
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
