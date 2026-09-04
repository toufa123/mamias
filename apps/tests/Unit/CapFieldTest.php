<?php

declare(strict_types=1);

use App\Filament\Forms\Components\CapField;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('services.cap.site_key', 'test-key');
});

it('builds a root-relative endpoint so the widget follows the browsed origin', function () {
    config()->set('services.cap.public_url', '/cap');

    expect(CapField::make('cap')->getApiEndpoint())
        ->toBe('/cap/test-key/')
        ->not->toStartWith('http');
});

it('defaults to the same-origin proxy path when no public url is configured', function () {
    config()->set('services.cap.public_url', null);

    expect(CapField::make('cap')->getApiEndpoint())->toBe('/cap/test-key/');
});

it('does not emit a double slash when the public url has a trailing slash', function () {
    config()->set('services.cap.public_url', '/cap/');

    expect(CapField::make('cap')->getApiEndpoint())->toBe('/cap/test-key/');
});

it('still honours an explicit absolute url', function () {
    config()->set('services.cap.public_url', 'https://captcha.example.org');

    expect(CapField::make('cap')->getApiEndpoint())
        ->toBe('https://captcha.example.org/test-key/');
});
