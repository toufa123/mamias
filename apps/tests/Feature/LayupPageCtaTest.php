<?php

use App\Models\User;
use Crumbls\Layup\Models\Page;

use function Pest\Laravel\get;

/**
 * The class names also appear inside the CTA's own stylesheet, so assertions
 * have to read the <body> tag rather than search the whole document.
 */
function layupBodyClasses(string $html): string
{
    preg_match('/<body\b[^>]*class="([^"]*)"/s', $html, $matches);

    return $matches[1] ?? '';
}

it('builds the page out of stored content, not a bespoke widget type', function (string $slug) {
    $types = collect(Page::where('slug', $slug)->sole()->content['rows'])
        ->flatMap(fn (array $row): array => $row['columns'])
        ->flatMap(fn (array $column): array => $column['widgets'])
        ->pluck('type')
        ->unique()
        ->values();

    expect($types->all())->toBe(['html']);
})->with(['home', 'about']);

it('marks the body as a guest and ships the register and sign-in buttons', function (string $url) {
    $response = get($url)->assertOk();

    expect(layupBodyClasses($response->content()))
        ->toContain('is-guest')
        ->not->toContain('is-authenticated');

    $response
        ->assertSee('Create Free Account')
        ->assertSee('Sign In');
})->with(['/', '/about']);

it('marks the body as authenticated for a signed-in reporter', function (string $url) {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $response = get($url)->assertOk();

    expect(layupBodyClasses($response->content()))
        ->toContain('is-authenticated')
        ->not->toContain('is-staff')
        ->not->toContain('is-guest');
})->with(['/', '/about']);

it('marks the body as staff for an admin and offers the admin area', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    $response = get('/')->assertOk();

    expect(layupBodyClasses($response->content()))->toContain('is-staff');

    $response->assertSee('Go to Admin Area');
});

it('stores root-relative links so the page works under any hostname', function (string $slug) {
    $content = json_encode(Page::where('slug', $slug)->sole()->content);

    expect($content)
        ->toContain('href=\"\/mamias')
        ->not->toContain('http://')
        ->not->toContain('https://');
})->with(['home', 'about']);
