<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Layup\Widgets\MamiasChartWidget;
use App\Livewire\NisData;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Models\User;
use App\Services\MediterraneanDashboard;
use Database\Seeders\LayupMediterraneanDashboardSeeder;
use Livewire\Livewire;

it('builds the phylum grids and the taxonomy tree', function () {
    $taxon = fn (string $phylum, string $family) => Taxon::factory()->state(['kingdom' => 'Animalia', 'phylum' => $phylum, 'class' => "{$phylum} class", 'family' => $family]);

    $mollusc = IntroEventRecord::factory()->for($taxon('Mollusca', 'Veneridae'))->create(['first_introduction_year' => 1972]);
    IntroEventRecord::factory()->for($taxon('Mollusca', 'Veneridae'))->create(['first_introduction_year' => 1995]);
    $fish = IntroEventRecord::factory()->for($taxon('Chordata', 'Gobiidae'))->create(['first_introduction_year' => 1995]);

    PathwayRecord::factory()->create(['intro_event_id' => $mollusc->id, 'category' => CbdPathwayCategory::Corridor]);
    SubregionRecord::factory()->create(['intro_event_id' => $fish->id, 'subregion' => Subregion::EMED]);

    $dashboard = app(MediterraneanDashboard::class);

    expect($dashboard->pathwaysByPhylum()['values'][0])->toBe([0, 0, 0, 0, 1, 0])
        ->and($dashboard->subregionsByPhylum())->toBe(['rows' => ['Chordata'], 'columns' => ['WMED', 'CMED', 'ADRIA', 'EMED'], 'values' => [[0, 0, 0, 1]]])
        ->and($dashboard->taxonomyTree()[0]['name'])->toBe('Animalia')
        ->and($dashboard->taxonomyTree()[0]['children'][0]['children'][0]['children'][0])->toBe(['name' => 'Veneridae', 'value' => 2]);
});

it('counts live introduction events for the headline and the decade trend', function () {
    $first = IntroEventRecord::factory()->create(['first_introduction_year' => 1985]);
    // Same taxon, second introduction event: counted as an event of its own.
    IntroEventRecord::factory()->create(['taxon_id' => $first->taxon_id, 'first_introduction_year' => 2010]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2003, 'establishment_status' => EstablishmentStatus::Casual]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2010]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2003])->delete();

    $dashboard = app(MediterraneanDashboard::class);

    expect($dashboard->headline())->toBe([
        'events' => 4,
        'established' => 3,
        'casual' => 1,
        'recent' => 3,
        'recentFrom' => 2001,
        'recentTo' => 2010,
    ])->and($dashboard->trend())->toBe([
        'labels' => ['1980s', '1990s', '2000s', '2010s'],
        'counts' => [1, 0, 1, 2],
        'cumulative' => [1, 1, 2, 4],
    ]);
});

it('counts only the validated baseline: NIS and unclassified events', function () {
    IntroEventRecord::factory()->create(['first_introduction_year' => 1985]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 1990, 'nis_status' => null]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2020, 'nis_status' => NisStatus::DataDeficient]);
    IntroEventRecord::factory()->create(['first_introduction_year' => 2020, 'nis_status' => NisStatus::Cryptogenic]);

    $dashboard = app(MediterraneanDashboard::class);

    expect($dashboard->headline()['events'])->toBe(2)
        ->and($dashboard->headline()['recentTo'])->toBe(1990)
        ->and($dashboard->trend()['labels'])->toBe(['1980s', '1990s']);
});

it('dates the sub-region spread by the event\'s first Mediterranean record, not the sub-region arrival year', function () {
    $early = IntroEventRecord::factory()->create(['first_introduction_year' => 1971]);
    $late = IntroEventRecord::factory()->create(['first_introduction_year' => 1995]);
    $undated = IntroEventRecord::factory()->create(['first_introduction_year' => null]);

    SubregionRecord::factory()->create(['intro_event_id' => $early->id, 'subregion' => Subregion::EMED, 'first_arrival_year' => 2015]);
    SubregionRecord::factory()->create(['intro_event_id' => $early->id, 'subregion' => Subregion::WMED, 'first_arrival_year' => null]);
    SubregionRecord::factory()->create(['intro_event_id' => $late->id, 'subregion' => Subregion::WMED, 'first_arrival_year' => 1960]);
    SubregionRecord::factory()->create(['intro_event_id' => $undated->id, 'subregion' => Subregion::ADRIA, 'first_arrival_year' => 1980]);

    expect(app(MediterraneanDashboard::class)->spread())->toBe([
        'labels' => ['1970s', '1980s', '1990s'],
        'series' => [
            'WMED' => [1, 0, 1],
            'CMED' => [0, 0, 0],
            'ADRIA' => [0, 0, 0],
            'EMED' => [1, 0, 0],
        ],
    ]);
});

it('stacks sub-regions by the sub-region\'s own status and drops empty stacks', function () {
    // The event's basin-level status is ignored: each sub-region record decides.
    $event = IntroEventRecord::factory()->create(['establishment_status' => EstablishmentStatus::Established]);
    SubregionRecord::factory()->create(['intro_event_id' => $event->id, 'subregion' => Subregion::CMED, 'establishment_status' => EstablishmentStatus::Vagrant]);
    SubregionRecord::factory()->create(['intro_event_id' => $event->id, 'subregion' => Subregion::EMED, 'establishment_status' => EstablishmentStatus::Invasive]);

    expect(app(MediterraneanDashboard::class)->statusBySubregion())->toBe([
        'subregions' => ['WMED', 'CMED', 'ADRIA', 'EMED'],
        'stacks' => ['Invasive' => [0, 0, 0, 1], 'Casual / vagrant' => [0, 1, 0, 0]],
    ]);
});

it('splits phyla by status and sub-regions by pathway', function () {
    $mollusc = IntroEventRecord::factory()->for(Taxon::factory()->state(['phylum' => 'Mollusca']))->create();
    IntroEventRecord::factory()->for(Taxon::factory()->state(['phylum' => 'Mollusca']))->create(['establishment_status' => EstablishmentStatus::Casual]);
    IntroEventRecord::factory()->for(Taxon::factory()->state(['phylum' => 'Chordata']))->create();

    SubregionRecord::factory()->create(['intro_event_id' => $mollusc->id, 'subregion' => Subregion::EMED]);
    PathwayRecord::factory()->create(['intro_event_id' => $mollusc->id, 'category' => CbdPathwayCategory::Corridor]);
    PathwayRecord::factory()->create(['intro_event_id' => $mollusc->id, 'category' => CbdPathwayCategory::TransportStowaway]);

    $dashboard = app(MediterraneanDashboard::class);

    expect($dashboard->statusByPhylum())->toBe([
        'phyla' => ['Mollusca', 'Chordata'],
        'stacks' => ['Established' => [1, 1], 'Casual / vagrant' => [1, 0]],
    ]);

    $series = $dashboard->pathwaysBySubregion()['series'];

    // An event with two pathways counts once in each.
    expect($series[CbdPathwayCategory::Corridor->getLabel()])->toBe([0, 0, 0, 1])
        ->and($series[CbdPathwayCategory::TransportStowaway->getLabel()])->toBe([0, 0, 0, 1])
        ->and($series[CbdPathwayCategory::Unaided->getLabel()])->toBe([0, 0, 0, 0]);
});

it('publishes the dashboard page, basin-wide charts before sub-region charts', function () {
    IntroEventRecord::factory()->create();
    $this->seed(LayupMediterraneanDashboardSeeder::class);

    $response = $this->get('/pages/dashboard/mediterranean')->assertOk();

    $response->assertSeeInOrder([
        'Reported NIS in the Mediterranean',
        'Mediterranean-wide',
        'data-mamias-chart="trend"',
        'data-mamias-chart="introduction-rate"',
        'data-mamias-chart="yearly-rate"',
        'data-mamias-chart="pathways"',
        'data-mamias-chart="taxonomy"',
        'data-mamias-chart="pathway-cloud"',
        'data-mamias-chart="family-cloud"',
        'data-mamias-chart="taxon-status"',
        'data-mamias-chart="phylum-pathways"',
        'data-mamias-chart="taxonomy-treemap"',
        'By EcAp sub-region',
        'data-mamias-chart="spread-bars"',
        'data-mamias-chart="subregion-status"',
        'data-mamias-chart="pathway-shares"',
        'data-mamias-chart="subregion-pathways"',
        'data-mamias-chart="phylum-subregions"',
        'data-mamias-chart="groups-subregions"',
        'data-mamias-chart="shared-subregions"',
        'data-mamias-chart="spread-map"',
    ], escape: false);

    // The spread map's last decade already shows reported NIS per sub-region.
    $response->assertDontSee('data-mamias-chart="subregion-map"', escape: false);
});

it('sizes the word clouds by reported NIS and links each word to the data explorer', function () {
    $veneridae = Taxon::factory()->state(['kingdom' => 'Animalia', 'phylum' => 'Mollusca', 'family' => 'Veneridae']);
    $shipped = IntroEventRecord::factory()->for($veneridae)->create();
    $alsoShipped = IntroEventRecord::factory()->for($veneridae)->create();
    $canal = IntroEventRecord::factory()->for(Taxon::factory()->state(['kingdom' => 'Plantae', 'phylum' => 'Rhodophyta', 'family' => 'Rhodomelaceae']))->create();
    foreach ([$shipped, $alsoShipped] as $event) {
        PathwayRecord::factory()->create(['intro_event_id' => $event->id, 'category' => CbdPathwayCategory::TransportStowaway, 'subcategory' => CbdPathwaySubcategory::TransportStowaway_3_1]);
    }
    PathwayRecord::factory()->create(['intro_event_id' => $canal->id, 'category' => CbdPathwayCategory::Corridor, 'subcategory' => CbdPathwaySubcategory::Corridor_5_1]);

    $pathways = MamiasChartWidget::payload('pathway-cloud', app(MediterraneanDashboard::class))['words'];
    $families = MamiasChartWidget::payload('family-cloud', app(MediterraneanDashboard::class))['words'];

    expect(array_column($pathways, 'name'))->toBe(['Shipping', 'Canals (Suez, Gibraltar)'])
        ->and($pathways[0])->toMatchArray(['value' => 2, 'group' => 3, 'full' => '3.1: Shipping (ballast water, hull fouling, sediments)'])
        ->and(array_column($families, 'name'))->toBe(['Veneridae', 'Rhodomelaceae'])
        ->and($families[1])->toMatchArray(['value' => 1, 'group' => 'Plantae']);

    // The links open the data explorer already filtered on the pathway, or searching the family.
    expect($pathways[0]['url'])->toBe(route('data', ['pathway' => '3.1']))
        ->and($families[0]['url'])->toBe(route('data', ['search' => 'Veneridae']));
    Livewire::withQueryParams(['pathway' => '3.1'])->test(NisData::class)
        ->assertCanSeeTableRecords([$shipped, $alsoShipped])
        ->assertCanNotSeeTableRecords([$canal]);
    Livewire::withQueryParams(['search' => 'Veneridae'])->test(NisData::class)
        ->assertCanSeeTableRecords([$shipped, $alsoShipped])
        ->assertCanNotSeeTableRecords([$canal]);
});

it('offers the PNG download of each chart to signed-in visitors only', function () {
    IntroEventRecord::factory()->create();
    $this->seed(LayupMediterraneanDashboardSeeder::class);

    $this->get('/pages/dashboard/mediterranean')->assertOk()->assertDontSee('data-chart-download', escape: false);

    $this->actingAs(User::factory()->create())
        ->get('/pages/dashboard/mediterranean')->assertOk()->assertSee('data-chart-download', escape: false);
});
