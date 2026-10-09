<?php

use App\Models\User;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

use function Pest\Laravel\get;

it('redirects unauthenticated users to login', function () {
    get('/references')
        ->assertRedirect('/login');
});

it('redirects /login to filament login', function () {
    get('/login')
        ->assertRedirect('/mamias/login');
});

it('redirects unverified authenticated users to verification notice', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    get('/references')
        ->assertRedirect('/email-verification/prompt');
});

it('redirects /email-verification/prompt to filament verification prompt', function () {
    get('/email-verification/prompt')
        ->assertRedirect('/mamias/email-verification/prompt');
});

it('lets a newly registered public user reach the verification prompt and verify', function () {
    $user = User::factory()->unverified()->create()->assignRole('user');
    $this->actingAs($user);

    get('/mamias/email-verification/prompt')->assertOk();

    get(Filament\Facades\Filament::getPanel('mamias')->getVerifyEmailUrl($user))
        ->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    // The rest of the panel stays closed to them.
    get('/mamias')->assertRedirect('/');
});

it('lets a public user make Livewire requests from the verification prompt', function () {
    $this->actingAs(User::factory()->unverified()->create()->assignRole('user'));

    $html = get('/mamias/email-verification/prompt')->assertOk()->getContent();

    preg_match('/wire:snapshot="([^"]+)"/', $html, $match);
    $snapshot = html_entity_decode($match[1], ENT_QUOTES);

    $this->withHeaders(['X-Livewire' => 'true'])
        ->postJson(app(HandleRequests::class)->getUpdateUri(), [
            'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => []]],
        ])
        ->assertOk();
});

it('sends an already-verified public user from the verification prompt to the home page', function () {
    $this->actingAs(User::factory()->create()->assignRole('user'));

    get('/mamias/email-verification/prompt')->assertRedirect('/');
});
