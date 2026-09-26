<?php

declare(strict_types=1);

use Database\Seeders\LayupLegalPagesSeeder;

it('publishes each legal page at its footer link', function (string $path, string $heading) {
    $this->seed(LayupLegalPagesSeeder::class);

    $this->get($path)
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('class="mamias-legal"', false)
        ->assertSee('atef.ouerghi@spa-rac.org');
})->with([
    ['/legal-notice', 'Legal notice'],
    ['/terms-of-use', 'Terms of use'],
    ['/cookies-policy', 'Cookies policy'],
]);

it('links the footer to the legal pages', function () {
    $this->seed(LayupLegalPagesSeeder::class);

    $this->get('/legal-notice')
        ->assertSee('href="'.url('terms-of-use').'"', false)
        ->assertSee('href="'.url('cookies-policy').'"', false);
});
