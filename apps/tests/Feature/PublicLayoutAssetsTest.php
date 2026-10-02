<?php

use function Pest\Laravel\get;

it('serves the cookie-consent script instead of letting the Layup catch-all 404 it', function () {
    get('/laravel-cookie-consent/script-utils')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript');
});

it('keeps panel-only plugin assets off the public site', function () {
    get('/')
        ->assertOk()
        ->assertDontSee('filament-file-manager/file-manager.css', false)
        ->assertDontSee('filament-logs-explorer', false)
        ->assertSee('filament-leaflet/leaflet-map.js', false);
});

it('loads the Vite app bundle as a module', function () {
    expect(get('/')->assertOk()->content())
        ->toMatch('#<script\s[^>]*src="[^"]*/build/assets/app-[^"]+\.js"[^>]*type="module"#');
});
