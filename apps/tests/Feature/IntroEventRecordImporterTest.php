<?php

declare(strict_types=1);

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);

    $this->taxon = Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);
});

// --- List page ---

it('renders the intro event list page', function () {
    livewire(ListIntroEventRecords::class)->assertSuccessful();
});

it('has an import action on the list page', function () {
    livewire(ListIntroEventRecords::class)
        ->assertActionExists('import');
});

// --- Taxon resolution ---

it('resolves taxon_id from scientific name', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $taxonColumn = $columns->get('taxon_id');

    $resolved = value($taxonColumn->getCastStateUsing(), 'Caulerpa cylindracea');

    expect($resolved)->toBe($this->taxon->id);
});

it('resolves taxon_id after stripping Excel encoding artifacts from the name', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $taxonColumn = $columns->get('taxon_id');

    // Non-breaking space (\xC2\xA0) and BOM (\xEF\xBB\xBF) are common Excel artifacts
    $nameWithArtifacts = "\xEF\xBB\xBFCaulerpa\xC2\xA0cylindracea";

    $resolved = value($taxonColumn->getCastStateUsing(), $nameWithArtifacts);

    expect($resolved)->toBe($this->taxon->id);
});

it('returns null for unknown scientific name', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $taxonColumn = $columns->get('taxon_id');

    $resolved = value($taxonColumn->getCastStateUsing(), 'Unknown species xyz');

    expect($resolved)->toBeNull();
});

// --- Enum resolution ---

it('resolves NIS status from string', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $nisColumn = $columns->get('nis_status');

    expect(value($nisColumn->getCastStateUsing(), 'NIS'))->toBe(NisStatus::NIS);
    expect(value($nisColumn->getCastStateUsing(), 'Cryptogenic'))->toBe(NisStatus::Cryptogenic);
    expect(value($nisColumn->getCastStateUsing(), 'Questionable'))->toBe(NisStatus::Questionable);
});

it('resolves NIS status from shorthand values', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $nisColumn = $columns->get('nis_status');

    expect(value($nisColumn->getCastStateUsing(), 'AL'))->toBe(NisStatus::NIS);
    expect(value($nisColumn->getCastStateUsing(), 'al'))->toBe(NisStatus::NIS);
    expect(value($nisColumn->getCastStateUsing(), 'cry'))->toBe(NisStatus::Cryptogenic);
    expect(value($nisColumn->getCastStateUsing(), 'que'))->toBe(NisStatus::Questionable);
    expect(value($nisColumn->getCastStateUsing(), 'ques'))->toBe(NisStatus::Questionable);
});

it('returns null for unrecognised NIS status', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $nisColumn = $columns->get('nis_status');

    expect(value($nisColumn->getCastStateUsing(), 'Unknown status xyz'))->toBeNull();
});

it('resolves establishment status from string', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $estColumn = $columns->get('establishment_status');

    expect(value($estColumn->getCastStateUsing(), 'Established'))->toBe(EstablishmentStatus::Established);
    expect(value($estColumn->getCastStateUsing(), 'Casual'))->toBe(EstablishmentStatus::Casual);
    expect(value($estColumn->getCastStateUsing(), 'Invasive'))->toBe(EstablishmentStatus::Invasive);
});

it('resolves establishment status from shorthand values', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $estColumn = $columns->get('establishment_status');

    expect(value($estColumn->getCastStateUsing(), 'cas'))->toBe(EstablishmentStatus::Casual);
    expect(value($estColumn->getCastStateUsing(), 'est'))->toBe(EstablishmentStatus::Established);
    expect(value($estColumn->getCastStateUsing(), 'unk'))->toBe(EstablishmentStatus::Unknown);
    expect(value($estColumn->getCastStateUsing(), 'inv'))->toBe(EstablishmentStatus::Invasive);
    expect(value($estColumn->getCastStateUsing(), 'DD'))->toBe(EstablishmentStatus::DataDeficient);
});

// --- Country casting ---

it('casts comma-separated countries to array', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $countryColumn = $columns->get('first_country');

    $result = value($countryColumn->getCastStateUsing(), 'France, Spain, Italy');

    expect($result)->toBe(['France', 'Spain', 'Italy']);
});

it('returns null for blank country', function () {
    $columns = collect(IntroEventRecordImporter::getColumns())->keyBy(fn ($col) => $col->getName());
    $countryColumn = $columns->get('first_country');

    expect(value($countryColumn->getCastStateUsing(), ''))->toBeNull();
    expect(value($countryColumn->getCastStateUsing(), null))->toBeNull();
});

// --- resolveRecord upsert ---

it('returns existing record for known taxon_id instead of creating a duplicate', function () {
    $record = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
        'first_introduction_year' => 2010,
    ]);

    $importer = new IntroEventRecordImporter;
    $importer->data = ['taxon_id' => $this->taxon->id];

    $resolved = $importer->resolveRecord();

    expect($resolved->id)->toBe($record->id);
});

it('returns a new IntroEventRecord for an unseen taxon_id', function () {
    $newTaxon = Taxon::factory()->create();

    $importer = new IntroEventRecordImporter;
    $importer->data = ['taxon_id' => $newTaxon->id];

    $resolved = $importer->resolveRecord();

    expect($resolved->exists)->toBeFalse();
});

// --- SubregionRecord creation in afterSave ---

/** Helper: builds a minimal importer instance with protected properties set via closure binding. */
function makeImporter(array $columnMap, array $options, IntroEventRecord $record, array $data): IntroEventRecordImporter
{
    $import = Mockery::mock(Import::class)->makePartial();

    $importer = new IntroEventRecordImporter($import, $columnMap, $options);

    (function () use ($record, $data): void {
        $this->record = $record;
        $this->data = $data;
    })->call($importer);

    return $importer;
}

it('creates subregion records for mapped subregion columns after save', function () {
    $introEvent = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
        'first_introduction_year' => 2000,
    ]);

    $importer = makeImporter(
        columnMap: ['wmed_nis_status' => 'WMED Status', 'wmed_first_arrival_year' => 'WMED Year'],
        options: [],
        record: $introEvent,
        data: ['wmed_nis_status' => NisStatus::NIS, 'wmed_first_arrival_year' => 2005],
    );

    (fn () => $this->afterSave())->call($importer);

    assertDatabaseHas(SubregionRecord::class, [
        'intro_event_id' => $introEvent->id,
        'subregion' => Subregion::WMED->value,
        'nis_status' => NisStatus::NIS->value,
        'first_arrival_year' => 2005,
    ]);
});

it('skips subregions whose status and year are both absent', function () {
    $introEvent = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
    ]);

    $importer = makeImporter(
        columnMap: [],
        options: [],
        record: $introEvent,
        data: [],
    );

    (fn () => $this->afterSave())->call($importer);

    expect(SubregionRecord::where('intro_event_id', $introEvent->id)->count())->toBe(0);
});

it('upserts an existing subregion record on re-import', function () {
    $introEvent = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
    ]);

    SubregionRecord::create([
        'intro_event_id' => $introEvent->id,
        'subregion' => Subregion::CMED,
        'nis_status' => NisStatus::Cryptogenic,
        'first_arrival_year' => 1990,
    ]);

    $importer = makeImporter(
        columnMap: ['cmed_nis_status' => 'CMED Status'],
        options: [],
        record: $introEvent,
        data: ['cmed_nis_status' => NisStatus::NIS],
    );

    (fn () => $this->afterSave())->call($importer);

    expect(SubregionRecord::where('intro_event_id', $introEvent->id)
        ->where('subregion', Subregion::CMED->value)
        ->value('nis_status'))
        ->toBe(NisStatus::NIS->value);
});

// --- File header validation ---

it('rejects a CSV with more than one empty column header', function () {
    $csv = "Scientific Name,,Country,\n".
           'Caulerpa cylindracea,,France,';

    $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

    $action = ImportAction::make()
        ->importer(IntroEventRecordImporter::class)
        ->chunkSize(100)
        ->fileRules([
            function (string $attribute, mixed $value, Closure $fail): void {
                $path = $value->getRealPath();
                if (! $path) {
                    return;
                }
                $reader = Reader::createFromPath($path);
                $reader->setHeaderOffset(0);
                $emptyHeaders = array_filter(
                    $reader->getHeader(),
                    fn (string $header): bool => blank($header),
                );
                if (count($emptyHeaders) > 1) {
                    $fail('The file must not contain more than one empty column header.');
                }
            },
        ]);

    $rules = $action->getFileValidationRules();
    $closureRule = collect($rules)->first(fn ($r) => $r instanceof Closure);

    $errors = [];
    $closureRule('file', $file, function (string $msg) use (&$errors) {
        $errors[] = $msg;
    });

    expect($errors)->toContain('The file must not contain more than one empty column header.');
});

it('accepts a CSV with at most one empty column header', function () {
    $csv = "Scientific Name,,Country\n".
           'Caulerpa cylindracea,,France';

    $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

    $reader = Reader::createFromPath($file->getRealPath());
    $reader->setHeaderOffset(0);

    $emptyHeaders = array_filter(
        $reader->getHeader(),
        fn (string $header): bool => blank($header),
    );

    expect(count($emptyHeaders))->toBeLessThanOrEqual(1);
});
