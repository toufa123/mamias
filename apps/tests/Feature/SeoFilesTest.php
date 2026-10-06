<?php

use App\Models\IntroEventRecord;
use App\Models\Taxon;

it('lists CMS pages, the data explorer and species pages in the sitemap and llms files', function () {
    $dir = storage_path('framework/testing/seo-'.uniqid());
    config([
        'filament-seo-files.sitemap.path' => "{$dir}/sitemap.xml",
        'filament-seo-files.llms.path' => "{$dir}/llms.txt",
        'filament-seo-files.llms.full_path' => "{$dir}/llms-full.txt",
    ]);
    // Imported events can lack both statuses.
    $event = IntroEventRecord::factory()
        ->for(Taxon::factory()->state(['scientificname' => 'Pterois miles']))
        ->create(['nis_status' => null, 'establishment_status' => null, 'first_introduction_year' => 1991]);

    $this->artisan('seo-files:sitemap')->assertSuccessful();
    $this->artisan('seo-files:llms')->assertSuccessful();

    expect(file_get_contents("{$dir}/sitemap.xml"))
        ->toContain('<loc>'.url('/').'/</loc>')
        ->toContain('<loc>'.url('/about').'</loc>')
        ->toContain('<loc>'.url('/pages/data').'</loc>')
        ->toContain('<loc>'.url('/pages/manual').'</loc>')
        ->toContain('<loc>'.url("/pages/data/{$event->id}").'</loc>')
        ->and(file_get_contents("{$dir}/llms.txt"))
        ->toContain('[Pterois miles]('.url("/pages/data/{$event->id}").'): First recorded in the Mediterranean in 1991.');

    File::deleteDirectory($dir);
});
