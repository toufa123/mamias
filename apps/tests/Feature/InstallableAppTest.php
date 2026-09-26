<?php

declare(strict_types=1);

use App\Models\User;

it('links the app manifest and shows the fullscreen control on the public site', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest" />', escape: false)
        ->assertSee('Full screen');
});

it('keeps the fullscreen control beside the user menu when signed in on the public site', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Full screen', $user->getFilamentName()]);
});

it('links the app manifest and shows the fullscreen control in the panel', function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'))
        ->get('/mamias')
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest" />', escape: false)
        ->assertSee('Full screen');
});

it('serves a manifest that browsers accept as installable', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray(['start_url' => '/', 'display' => 'standalone'])
        ->and(collect($manifest['icons'])->pluck('sizes')->all())->toContain('192x192', '512x512');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
});
