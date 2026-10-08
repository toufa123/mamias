<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * resources/js/tour.js finds its steps by these anchors and skips any it
 * cannot find, so a renamed or dropped one fails silently in the browser.
 */

it('anchors the Explore MAMIAS pages and offers a replay from the menu', function () {
    get('/pages/data')->assertOk()
        ->assertSee('data-tour-start', false)
        ->assertSee('data-tour="data-filters"', false)
        ->assertSee('data-tour="data-table"', false)
        ->assertSee('data-tour="data-guide"', false);

    get('/pages/map')->assertOk()
        ->assertSee('data-tour="map-filters"', false)
        ->assertSee('data-tour="map-layer"', false)
        ->assertSee('data-tour="map-summary"', false)
        ->assertSee('data-tour="map-species"', false);
});

it('anchors the My Bibliographic References page', function () {
    actingAs(User::factory()->create()->assignRole('user'));

    get('/references')->assertOk()
        ->assertSee('data-tour="references-actions"', false);
});
