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

// Stored html throughout, editable in the page builder; the home page's one
// exception is its live "MAMIAS at a glance" figures (MamiasSummaryWidget).
it('builds the page out of stored content, not a bespoke widget type', function (string $slug, array $expected) {
    $types = collect(Page::where('slug', $slug)->sole()->content['rows'])
        ->flatMap(fn (array $row): array => $row['columns'])
        ->flatMap(fn (array $column): array => $column['widgets'])
        ->pluck('type')
        ->unique()
        ->values();

    expect($types->all())->toBe($expected);
})->with([
    'home' => ['home', ['html', 'mamias-summary']],
    'about' => ['about', ['html']],
]);

it('marks the body as a guest', function (string $url) {
    $response = get($url)->assertOk();

    expect(layupBodyClasses($response->content()))
        ->toContain('is-guest')
        ->not->toContain('is-authenticated');
})->with(['/', '/about']);

// The About page dropped its "Get Involved" block, so the CTA lives on home only.
it('ships the register and sign-in buttons on the home page', function () {
    get('/')
        ->assertOk()
        ->assertSee('Create Free Account')
        ->assertSee('Sign In');
});

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

// External citations (MED QSR, UNEP documents) are absolute by nature; only
// links back into this app must stay root-relative.
it('stores root-relative links so the page works under any hostname', function (string $slug) {
    $content = json_encode(Page::where('slug', $slug)->sole()->content);

    expect($content)
        ->toContain('href=\"\/')
        ->not->toContain('http://')
        ->not->toMatch('#https:\\\\/\\\\/[^"\\\\]*(mamias|localhost|127\.0\.0\.1)#i');
})->with(['home', 'about']);
