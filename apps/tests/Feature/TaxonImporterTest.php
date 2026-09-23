<?php

declare(strict_types=1);

use App\Filament\Imports\TaxonImporter;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    // WoRMS answers with whatever a test puts here; nothing by default, so rows
    // fall back to the literal name check. Stubs are matched in registration
    // order, so a per-test Http::fake() would never be reached past a catch-all.
    $this->wormsRecords = [];
    Http::fake(fn () => $this->wormsRecords === []
        ? Http::response([], 204)
        : Http::response($this->wormsRecords));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->import = Import::create([
        'file_name' => 'taxa.csv',
        'file_path' => 'imports/taxa.csv',
        'importer' => TaxonImporter::class,
        'processed_rows' => 0,
        'total_rows' => 3,
        'successful_rows' => 0,
        'user_id' => $this->user->id,
    ]);
});

/** Runs one CSV row through the taxon importer. */
function importTaxonRow(Import $import, string $scientificname): void
{
    $importer = new TaxonImporter($import, ['scientificname' => 'scientificname'], []);

    $importer(['scientificname' => $scientificname]);
}

it('imports a scientific name that is not in the database yet', function () {
    importTaxonRow($this->import, 'Caulerpa cylindracea');

    expect(Taxon::where('scientificname', 'Caulerpa cylindracea')->exists())->toBeTrue();
});

it('skips a row whose scientific name already exists', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);

    expect(fn () => importTaxonRow($this->import, 'Caulerpa cylindracea'))
        ->toThrow(RowImportFailedException::class, TaxonImporter::DUPLICATE_ROW_MESSAGE);

    expect(Taxon::where('scientificname', 'Caulerpa cylindracea')->count())->toBe(1);
});

it('skips a duplicate whose name only matches after normalization', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);

    // Excel artifacts: BOM plus a non-breaking space inside the name.
    expect(fn () => importTaxonRow($this->import, "\xEF\xBB\xBFCaulerpa\xC2\xA0cylindracea"))
        ->toThrow(RowImportFailedException::class);

    expect(Taxon::count())->toBe(1);
});

it('skips a duplicate that is only in the recycle bin', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea'])->delete();

    expect(fn () => importTaxonRow($this->import, 'Caulerpa cylindracea'))
        ->toThrow(RowImportFailedException::class);

    expect(Taxon::withTrashed()->count())->toBe(1);
});

/** Makes WoRMS resolve $provided to the accepted Caulerpa cylindracea / 578163. */
function fakeWormsSynonymResponse(string $provided): void
{
    test()->wormsRecords = [[
        'AphiaID' => 1111111,
        'scientificname' => $provided,
        'status' => 'unaccepted',
        'valid_AphiaID' => 578163,
        'valid_name' => 'Caulerpa cylindracea',
    ]];
}

it('skips a row WoRMS resolves onto an existing AphiaID', function () {
    // Name deliberately unlike the accepted one, so only the AphiaID can match.
    Taxon::factory()->create(['scientificname' => 'Percnon gibbesi', 'aphia_id' => 578163]);

    fakeWormsSynonymResponse('Caulerpa racemosa var. cylindracea');

    expect(fn () => importTaxonRow($this->import, 'Caulerpa racemosa var. cylindracea'))
        ->toThrow(RowImportFailedException::class, TaxonImporter::WORMS_DUPLICATE_MESSAGE_PREFIX.'Percnon gibbesi');

    expect(Taxon::count())->toBe(1);
});

it('skips a row WoRMS resolves onto an existing accepted name', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea', 'aphia_id' => null]);

    fakeWormsSynonymResponse('Caulerpa racemosa var. cylindracea');

    expect(fn () => importTaxonRow($this->import, 'Caulerpa racemosa var. cylindracea'))
        ->toThrow(RowImportFailedException::class, TaxonImporter::WORMS_DUPLICATE_MESSAGE_PREFIX.'Caulerpa cylindracea');

    expect(Taxon::count())->toBe(1);
});

it('imports a WoRMS synonym whose accepted taxon is not in the catalogue', function () {
    fakeWormsSynonymResponse('Caulerpa racemosa var. cylindracea');

    importTaxonRow($this->import, 'Caulerpa racemosa var. cylindracea');

    expect(Taxon::pluck('scientificname')->all())->toBe(['Caulerpa racemosa var. cylindracea']);
});

it('normalizes a name exactly once, as the model does on save', function () {
    // Two passes would turn this into "Guttulina", a different record entirely.
    importTaxonRow($this->import, 'Guttulina? sp. Ex Entosigmomorphina sp.');

    expect(Taxon::pluck('scientificname')->all())->toBe(['Guttulina sp.']);
});

it('skips an insert-time collision without poisoning the surrounding transaction', function () {
    Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);

    // A name the duplicate check cannot see coming — a parallel chunk inserting
    // it first. Only the insert can catch it, and in Postgres a failed statement
    // aborts the whole chunk transaction unless it runs inside a savepoint.
    $importer = new TaxonImporter($this->import, ['scientificname' => 'scientificname'], []);
    (function (): void {
        $this->record = new Taxon(['scientificname' => 'Caulerpa cylindracea']);
        $this->data = ['scientificname' => 'Caulerpa cylindracea'];
    })->call($importer);

    DB::transaction(function () use ($importer): void {
        expect(fn () => $importer->saveRecord())
            ->toThrow(RowImportFailedException::class, TaxonImporter::DUPLICATE_ROW_MESSAGE);

        // ImportCsv keeps processing the chunk after a skipped row.
        importTaxonRow($this->import, 'Percnon gibbesi');
    });

    expect(Taxon::pluck('scientificname')->all())
        ->toBe(['Caulerpa cylindracea', 'Percnon gibbesi']);
});

it('reports skipped duplicates separately from failures in the completion notification', function () {
    $this->import->update(['successful_rows' => 1, 'total_rows' => 4]);

    $this->import->failedRows()->create([
        'data' => ['Scientific Name' => 'Caulerpa cylindracea'],
        'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE,
    ]);
    $this->import->failedRows()->create([
        'data' => ['Scientific Name' => 'Caulerpa racemosa var. cylindracea'],
        'validation_error' => TaxonImporter::WORMS_DUPLICATE_MESSAGE_PREFIX.'Caulerpa cylindracea, which is already in the database. Row not imported.',
    ]);
    $this->import->failedRows()->create([
        'data' => ['Scientific Name' => ''],
        'validation_error' => 'The Scientific Name field is required.',
    ]);

    expect(TaxonImporter::getSkippedRowsCount($this->import))->toBe(2);

    $body = TaxonImporter::getCompletedNotificationBody($this->import->fresh());

    expect($body)
        ->toContain('1 row imported')
        ->toContain('2 species skipped because they are already in the database')
        ->toContain('1 row failed to import');
});

it('downloads the not imported species as an Excel file', function () {
    $this->import->failedRows()->create([
        'data' => ['Scientific Name' => 'Caulerpa cylindracea'],
        'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE,
    ]);

    $response = $this->get(route('imports.not-imported-rows.download', ['import' => $this->import, 'format' => 'xlsx']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // An XLSX file is a ZIP archive; OpenSpout writes the cell values inline in the sheet.
    $file = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($file, $response->streamedContent());

    $archive = new ZipArchive;
    expect($archive->open($file))->toBeTrue();

    $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
    $archive->close();
    unlink($file);

    expect($sheet)
        ->toContain('Row')
        ->toContain('Scientific Name')
        ->toContain('Caulerpa cylindracea')
        ->toContain(TaxonImporter::DUPLICATE_ROW_MESSAGE);
});

it('numbers the not imported species in the CSV download', function () {
    $this->import->failedRows()->createMany([
        ['data' => ['Scientific Name' => 'Caulerpa cylindracea'], 'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE],
        ['data' => ['Scientific Name' => 'Percnon gibbesi'], 'validation_error' => TaxonImporter::DUPLICATE_ROW_MESSAGE],
    ]);

    $response = $this->get(route('imports.not-imported-rows.download', ['import' => $this->import, 'format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    // OpenSpout writes a UTF-8 BOM and quotes values containing spaces.
    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));

    expect($lines[0])->toContain('Row,"Scientific Name",Reason')
        ->and($lines[1])->toStartWith('1,"Caulerpa cylindracea"')
        ->and($lines[2])->toStartWith('2,"Percnon gibbesi"');
});

it('does not let another user download an import they do not own', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('imports.not-imported-rows.download', ['import' => $this->import, 'format' => 'xlsx']))
        ->assertForbidden();
});
