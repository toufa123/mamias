<?php

use App\Models\User;

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
