<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use League\Csv\Reader;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);

    // The app grants super_admin via seeded Shield permissions, which aren't run
    // in tests; mirror that intent with a super_admin gate bypass.
    Gate::before(fn (User $user) => $user->hasRole('super_admin') ? true : null);

    $this->taxon = Taxon::factory()->create(['scientificname' => 'Caulerpa cylindracea']);

    // Never hit the live WoRMS API in tests. Each test that exercises WoRMS sets its
    // own single fake; preventStrayRequests catches any unexpected outbound call.
    Cache::flush();
    Http::preventStrayRequests();
});

/** Fetch a configured import column by name. */
function importColumn(string $name)
{
    return collect(IntroEventRecordImporter::getColumns())
        ->keyBy(fn ($col) => $col->getName())
        ->get($name);
}

/** Run a column's cast pipeline for the given raw value (Filament v5 API). */
function castColumn(string $name, mixed $state): mixed
{
    return importColumn($name)->castState($state);
}

/** Builds a minimal importer instance with protected properties set via closure binding. */
function makeImporter(array $columnMap, array $options, IntroEventRecord $record, array $data, array $originalData = []): IntroEventRecordImporter
{
    $import = Mockery::mock(Import::class)->makePartial();

    $importer = new IntroEventRecordImporter($import, $columnMap, $options);

    (function () use ($record, $data, $originalData): void {
        $this->record = $record;
        $this->data = $data;
        $this->originalData = $originalData;
    })->call($importer);

    return $importer;
}

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
    expect(castColumn('taxon_id', 'Caulerpa cylindracea'))->toBe($this->taxon->id);
});

it('resolves taxon_id after stripping Excel encoding artifacts from the name', function () {
    // Non-breaking space (\xC2\xA0) and BOM (\xEF\xBB\xBF) are common Excel artifacts
    $nameWithArtifacts = "\xEF\xBB\xBFCaulerpa\xC2\xA0cylindracea";

    expect(castColumn('taxon_id', $nameWithArtifacts))->toBe($this->taxon->id);
});

it('returns null for unknown scientific name when WoRMS has no match', function () {
    Http::fake(['*' => Http::response([], 204)]);

    expect(castColumn('taxon_id', 'Unknown species xyz'))->toBeNull();
});

it('resolves taxon_id via WoRMS accepted name when there is no local exact match', function () {
    $accepted = Taxon::factory()->create([
        'scientificname' => 'Percnon gibbesi',
        'aphia_id' => 444777,
    ]);

    // The provided name is an unaccepted synonym; WoRMS redirects to the valid name/AphiaID.
    Http::fake([
        '*AphiaRecordsByName*' => Http::response([[
            'AphiaID' => 111222,
            'scientificname' => 'Acanthopus gibbesii',
            'status' => 'unaccepted',
            'valid_AphiaID' => 444777,
            'valid_name' => 'Percnon gibbesi',
        ]], 200),
    ]);

    expect(castColumn('taxon_id', 'Acanthopus gibbesii'))->toBe($accepted->id);
});

// --- Enum resolution ---

it('resolves NIS status from string', function () {
    expect(castColumn('nis_status', 'NIS'))->toBe(NisStatus::NIS);
    expect(castColumn('nis_status', 'Cryptogenic'))->toBe(NisStatus::Cryptogenic);
    expect(castColumn('nis_status', 'Questionable'))->toBe(NisStatus::Questionable);
});

it('resolves NIS status from shorthand values', function () {
    expect(castColumn('nis_status', 'AL'))->toBe(NisStatus::NIS);
    expect(castColumn('nis_status', 'al'))->toBe(NisStatus::NIS);
    expect(castColumn('nis_status', 'AL?'))->toBe(NisStatus::NIS); // trailing "?" tolerated
    expect(castColumn('nis_status', 'cry'))->toBe(NisStatus::Cryptogenic);
    expect(castColumn('nis_status', 'que'))->toBe(NisStatus::Questionable);
    expect(castColumn('nis_status', 'ques'))->toBe(NisStatus::Questionable);
});

it('returns null for unrecognised NIS status', function () {
    expect(castColumn('nis_status', 'Unknown status xyz'))->toBeNull();
});

it('resolves establishment status from string', function () {
    expect(castColumn('establishment_status', 'Established'))->toBe(EstablishmentStatus::Established);
    expect(castColumn('establishment_status', 'Casual'))->toBe(EstablishmentStatus::Casual);
    expect(castColumn('establishment_status', 'Invasive'))->toBe(EstablishmentStatus::Invasive);
});

it('resolves establishment status from shorthand and messy values', function () {
    expect(castColumn('establishment_status', 'cas'))->toBe(EstablishmentStatus::Casual);
    expect(castColumn('establishment_status', 'est'))->toBe(EstablishmentStatus::Established);
    expect(castColumn('establishment_status', 'unk'))->toBe(EstablishmentStatus::Unknown);
    expect(castColumn('establishment_status', 'inv'))->toBe(EstablishmentStatus::Invasive);
    expect(castColumn('establishment_status', 'DD'))->toBe(EstablishmentStatus::DataDeficient);
    expect(castColumn('establishment_status', 'QR'))->toBe(EstablishmentStatus::Questionable);
    // "rex" (range expansion) is a NisStatus value, not an establishment status —
    // EstablishmentStatus has no such case, so it must fall through to null.
    expect(castColumn('establishment_status', 'rex'))->toBeNull();
    expect(castColumn('nis_status', 'rex'))->toBe(NisStatus::RangeExpansion);
    expect(castColumn('establishment_status', 'est?'))->toBe(EstablishmentStatus::Established);   // trailing "?"
    expect(castColumn('establishment_status', 'unk (FR)'))->toBe(EstablishmentStatus::Unknown);   // parenthetical
    expect(castColumn('establishment_status', 'est/est'))->toBe(EstablishmentStatus::Established); // slash-duplicate
});

it('returns null for genuinely ambiguous establishment values', function () {
    expect(castColumn('establishment_status', 'cry-ex'))->toBeNull();
    expect(castColumn('establishment_status', 'partly native'))->toBeNull();
});

// --- Year casting ---

it('casts a clean 4-digit year within range', function () {
    expect(castColumn('first_introduction_year', '2018'))->toBe(2018);
});

it('returns null for ambiguous or out-of-range years', function () {
    expect(castColumn('first_introduction_year', '1965-67'))->toBeNull();
    expect(castColumn('first_introduction_year', '1965-67 not 88'))->toBeNull();
    expect(castColumn('first_introduction_year', '<1929'))->toBeNull();
    expect(castColumn('first_introduction_year', '1970s'))->toBeNull();
    expect(castColumn('first_introduction_year', '2004?'))->toBeNull();
    expect(castColumn('first_introduction_year', '1700'))->toBeNull(); // before 1800
    expect(castColumn('first_introduction_year', ''))->toBeNull();
});

// --- Country casting ---

it('casts comma-separated countries to array', function () {
    expect(castColumn('first_country', 'France, Spain, Italy'))->toBe(['France', 'Spain', 'Italy']);
});

it('returns null for blank country', function () {
    expect(castColumn('first_country', ''))->toBeNull();
    expect(castColumn('first_country', null))->toBeNull();
});

// --- resolveRecord upsert ---

it('returns existing record for known taxon_id instead of creating a duplicate', function () {
    $record = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
        'first_introduction_year' => 2010,
    ]);

    $importer = makeImporter(
        columnMap: [],
        options: [],
        record: new IntroEventRecord,
        data: ['taxon_id' => $this->taxon->id],
    );

    expect($importer->resolveRecord()->id)->toBe($record->id);
});

it('returns a new IntroEventRecord for an unseen taxon_id', function () {
    $newTaxon = Taxon::factory()->create();

    $importer = makeImporter(
        columnMap: [],
        options: [],
        record: new IntroEventRecord,
        data: ['taxon_id' => $newTaxon->id],
    );

    expect($importer->resolveRecord()->exists)->toBeFalse();
});

// --- needs_review flagging in afterFill ---

it('flags needs_review and preserves an ambiguous year in notes', function () {
    $record = new IntroEventRecord;

    $importer = makeImporter(
        columnMap: ['first_introduction_year' => 'Year'],
        options: [],
        record: $record,
        data: ['first_introduction_year' => null],
        originalData: ['Year' => '1965-67'],
    );

    (fn () => $this->afterFill())->call($importer);

    expect($record->needs_review)->toBeTrue()
        ->and($record->notes)->toContain('1965-67');
});

it('does not flag needs_review when watched values resolve cleanly', function () {
    $record = new IntroEventRecord;

    $importer = makeImporter(
        columnMap: ['first_introduction_year' => 'Year', 'nis_status' => 'Status'],
        options: [],
        record: $record,
        data: ['first_introduction_year' => 2018, 'nis_status' => NisStatus::NIS],
        originalData: ['Year' => '2018', 'Status' => 'AL'],
    );

    (fn () => $this->afterFill())->call($importer);

    expect($record->needs_review)->toBeFalse();
});

// --- SubregionRecord creation in afterSave ---

it('creates subregion records with establishment status for mapped columns', function () {
    $introEvent = IntroEventRecord::create([
        'taxon_id' => $this->taxon->id,
        'first_introduction_year' => 2000,
    ]);

    $importer = makeImporter(
        columnMap: ['wmed_establishment_status' => 'WMED Status', 'wmed_first_arrival_year' => 'WMED Year'],
        options: [],
        record: $introEvent,
        data: ['wmed_establishment_status' => EstablishmentStatus::Established, 'wmed_first_arrival_year' => 2005],
    );

    (fn () => $this->afterSave())->call($importer);

    assertDatabaseHas(SubregionRecord::class, [
        'intro_event_id' => $introEvent->id,
        'subregion' => Subregion::WMED->value,
        'establishment_status' => EstablishmentStatus::Established->value,
        'first_arrival_year' => 2005,
    ]);
});

it('preserves an ambiguous subregion year in the subregion record notes', function () {
    $introEvent = IntroEventRecord::create(['taxon_id' => $this->taxon->id]);

    $importer = makeImporter(
        columnMap: ['cmed_first_arrival_year' => 'CMED Year'],
        options: [],
        record: $introEvent,
        data: ['cmed_first_arrival_year' => null],
        originalData: ['CMED Year' => '1963-66'],
    );

    (fn () => $this->afterSave())->call($importer);

    expect(SubregionRecord::where('intro_event_id', $introEvent->id)
        ->where('subregion', Subregion::CMED->value)
        ->first()->notes)
        ->toContain('1963-66');
});

it('skips subregions whose status and year are both absent', function () {
    $introEvent = IntroEventRecord::create(['taxon_id' => $this->taxon->id]);

    $importer = makeImporter(columnMap: [], options: [], record: $introEvent, data: []);

    (fn () => $this->afterSave())->call($importer);

    expect(SubregionRecord::where('intro_event_id', $introEvent->id)->count())->toBe(0);
});

it('upserts an existing subregion record on re-import', function () {
    $introEvent = IntroEventRecord::create(['taxon_id' => $this->taxon->id]);

    SubregionRecord::create([
        'intro_event_id' => $introEvent->id,
        'subregion' => Subregion::CMED,
        'establishment_status' => EstablishmentStatus::Casual,
        'first_arrival_year' => 1990,
    ]);

    $importer = makeImporter(
        columnMap: ['cmed_establishment_status' => 'CMED Status'],
        options: [],
        record: $introEvent,
        data: ['cmed_establishment_status' => EstablishmentStatus::Established],
    );

    (fn () => $this->afterSave())->call($importer);

    expect(SubregionRecord::where('intro_event_id', $introEvent->id)
        ->where('subregion', Subregion::CMED->value)
        ->first()->establishment_status)
        ->toBe(EstablishmentStatus::Established);
});

// --- PathwayRecord creation in afterSave ---

it('creates pathway records for flagged pathway columns', function () {
    $introEvent = IntroEventRecord::create(['taxon_id' => $this->taxon->id]);

    $importer = makeImporter(
        columnMap: ['pathway_corridor' => 'CORRIDOR', 'pathway_stowaway_ballast' => 'BALLAST'],
        options: [],
        record: $introEvent,
        data: ['pathway_corridor' => true, 'pathway_stowaway_ballast' => true],
    );

    (fn () => $this->afterSave())->call($importer);

    assertDatabaseHas(PathwayRecord::class, [
        'intro_event_id' => $introEvent->id,
        'category' => CbdPathwayCategory::Corridor->value,
        'subcategory' => CbdPathwaySubcategory::Corridor_5_1->value,
    ]);

    assertDatabaseHas(PathwayRecord::class, [
        'intro_event_id' => $introEvent->id,
        'category' => CbdPathwayCategory::TransportStowaway->value,
        'subcategory' => CbdPathwaySubcategory::TransportStowaway_3_1->value,
    ]);
});

it('does not create pathway records when no pathway column is flagged', function () {
    $introEvent = IntroEventRecord::create(['taxon_id' => $this->taxon->id]);

    $importer = makeImporter(
        columnMap: ['pathway_corridor' => 'CORRIDOR'],
        options: [],
        record: $introEvent,
        data: ['pathway_corridor' => false],
    );

    (fn () => $this->afterSave())->call($importer);

    expect(PathwayRecord::where('intro_event_id', $introEvent->id)->count())->toBe(0);
});

// --- File header validation ---

it('detects more than one empty column header in a CSV', function () {
    $csv = "Scientific Name,,Country,\n".
           'Caulerpa cylindracea,,France,';

    $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

    $reader = Reader::createFromPath($file->getRealPath());
    $reader->setHeaderOffset(0);

    $emptyHeaders = array_filter(
        $reader->getHeader(),
        fn (string $header): bool => blank($header),
    );

    expect(count($emptyHeaders))->toBeGreaterThan(1);
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
