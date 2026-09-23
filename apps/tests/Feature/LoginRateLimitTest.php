<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/**
 * The parent Filament page already limits by IP. These cover the per-account
 * ceiling added on top of it, which an IP-keyed limiter cannot provide.
 *
 * Attempt counts stay under 5 so the parent's own per-IP bucket — which shares
 * this test's single request IP — never trips and masks what is being asserted.
 */
function loginRateLimitKey(string $email): string
{
    return 'mamias-login:'.sha1($email);
}

it('counts failed attempts against the account, not just the address', function () {
    User::factory()->create(['email' => 'victim@example.test']);

    $component = Livewire::test(Login::class);

    foreach (range(1, 3) as $ignored) {
        $component->fillForm([
            'email' => 'victim@example.test',
            'password' => 'wrong-password',
        ])->call('authenticate');
    }

    $this->assertGuest();
    expect(RateLimiter::attempts(loginRateLimitKey('victim@example.test')))->toBe(3);
});

it('keys the counter by email so one account cannot exhaust another', function () {
    User::factory()->create(['email' => 'victim@example.test']);

    $component = Livewire::test(Login::class);

    foreach (range(1, 3) as $ignored) {
        $component->fillForm([
            'email' => 'victim@example.test',
            'password' => 'wrong-password',
        ])->call('authenticate');
    }

    expect(RateLimiter::attempts(loginRateLimitKey('other@example.test')))->toBe(0);
});

it('clears the account counter after a successful sign-in', function () {
    User::factory()->create(['email' => 'victim@example.test'])->assignRole('super_admin');

    $component = Livewire::test(Login::class);

    foreach (range(1, 3) as $ignored) {
        $component->fillForm([
            'email' => 'victim@example.test',
            'password' => 'wrong-password',
        ])->call('authenticate');
    }

    $component->fillForm([
        'email' => 'victim@example.test',
        'password' => 'password', // UserFactory's default
    ])->call('authenticate');

    $this->assertAuthenticated();
    expect(RateLimiter::attempts(loginRateLimitKey('victim@example.test')))->toBe(0);
});
