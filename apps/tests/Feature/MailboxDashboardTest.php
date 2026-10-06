<?php

use App\Models\User;

it('serves the mailbox dashboard to any user', function () {
    $this->actingAs(User::factory()->create()->assignRole('user'))
        ->get('/mamias/mailbox')
        ->assertOk();
});

it('serves the mailbox dashboard to guests', function () {
    $this->get('/mamias/mailbox')->assertOk();
});
