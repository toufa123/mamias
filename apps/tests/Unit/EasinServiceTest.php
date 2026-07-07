<?php

declare(strict_types=1);

use App\Services\EasinService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('returns null for short scientific names', function () {
    $service = new EasinService;
    expect($service->fetchEasinId('Ab'))->toBeNull();
});

it('returns null when API returns empty array', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response([], 200),
    ]);

    $service = new EasinService;
    Cache::flush();

    expect($service->fetchEasinId('Caulerpa cylindracea'))->toBeNull();
});

it('returns null when API request fails', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response(null, 500),
    ]);

    $service = new EasinService;
    Cache::flush();

    expect($service->fetchEasinId('Caulerpa cylindracea'))->toBeNull();
});

it('extracts EASIN ID from response with uppercase EASINID key', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response([
            ['EASINID' => 'SP000123'],
        ], 200),
    ]);

    $service = new EasinService;
    Cache::flush();

    expect($service->fetchEasinId('Caulerpa cylindracea'))->toBe('SP000123');
});

it('extracts EASIN ID from response with easinId key', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response([
            ['easinId' => 'SP000456'],
        ], 200),
    ]);

    $service = new EasinService;
    Cache::flush();

    expect($service->fetchEasinId('Caulerpa cylindracea'))->toBe('SP000456');
});

it('caches the result for 24 hours', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response([
            ['EASINID' => 'SP000789'],
        ], 200),
    ]);

    $service = new EasinService;
    Cache::flush();

    $service->fetchEasinId('Caulerpa cylindracea');

    expect(Cache::has('easin_id_'.md5('Caulerpa cylindracea')))->toBeTrue();
});

it('logs errors when API request fails', function () {
    Http::fake([
        'easin.jrc.ec.europa.eu/*' => Http::response(null, 500),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'EASIN API request failed')
                && isset($context['scientific_name']);
        });

    $service = new EasinService;
    Cache::flush();

    $service->fetchEasinId('Caulerpa cylindracea');
});
