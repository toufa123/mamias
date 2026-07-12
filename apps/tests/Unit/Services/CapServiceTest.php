<?php

declare(strict_types=1);

use App\Services\CapService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Configure CAP for tests
    config()->set('services.cap.site_key', 'test-key');
    config()->set('services.cap.secret_key', 'test-secret');
});

it('detects when cap is not configured', function () {
    config()->set('services.cap.site_key', '');
    config()->set('services.cap.secret_key', '');

    $service = new CapService;

    expect($service->isConfigured())->toBeFalse();
});

it('detects when cap is configured', function () {
    $service = new CapService;

    expect($service->isConfigured())->toBeTrue();
});

it('returns false when token is empty', function () {
    $service = new CapService;

    expect($service->verifyToken(null))->toBeFalse()
        ->and($service->verifyToken(''))->toBeFalse();
});

it('verifies a valid token via the CAP API', function () {
    Http::fake([
        'cap:3000/*' => Http::response(['success' => true], 200),
    ]);

    $service = new CapService;

    expect($service->verifyToken('valid-token'))->toBeTrue();
});

it('rejects an invalid token', function () {
    Http::fake([
        'cap:3000/*' => Http::response(['success' => false], 200),
    ]);

    $service = new CapService;

    expect($service->verifyToken('invalid-token'))->toBeFalse();
});

it('returns false on API failure', function () {
    Http::fake([
        'cap:3000/*' => Http::response(null, 500),
    ]);

    $service = new CapService;

    expect($service->verifyToken('token'))->toBeFalse();
});
