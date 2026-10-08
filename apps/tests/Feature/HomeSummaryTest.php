<?php

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;

use function Pest\Laravel\get;

it('shows MAMIAS at a glance on the home page, from the dashboard baseline', function () {
    $recent = IntroEventRecord::factory()->create([
        'nis_status' => NisStatus::NIS,
        'establishment_status' => EstablishmentStatus::Established,
        'first_introduction_year' => 2020,
        'first_country' => ['Gaza strip'],
    ]);
    SubregionRecord::factory()->for($recent, 'introEvent')->create(['subregion' => Subregion::EMED]);
    // Outside the validated baseline: not counted, not listed.
    $cryptogenic = IntroEventRecord::factory()->create(['nis_status' => NisStatus::Cryptogenic, 'first_introduction_year' => 2021]);

    get('/')
        ->assertOk()
        ->assertSee('MAMIAS at a glance')
        ->assertSee('Reported species by EcAp sub-region')
        ->assertSee(route('map', ['subregion' => 'EMED']), false)
        ->assertSee(route('data.species', $recent), false)
        ->assertSee($recent->taxon->scientificname)
        ->assertSee('Israel')
        ->assertDontSee($cryptogenic->taxon->scientificname)
        ->assertSee(url('pages/dashboard/mediterranean'), false)
        ->assertSee('data-open-explore-menu', false)
        ->assertSee('id="explore-menu"', false);
});
