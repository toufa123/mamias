<?php

declare(strict_types=1);

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns false for null phone', function () {
    $service = new WhatsAppService;
    expect($service->isRegistered(null))->toBeFalse();
});

it('returns false for empty phone', function () {
    $service = new WhatsAppService;
    expect($service->isRegistered(''))->toBeFalse();
});

it('returns false for invalid phone number', function () {
    $service = new WhatsAppService;
    expect($service->isRegistered('abc'))->toBeFalse();
});

it('returns false for too short phone number', function () {
    $service = new WhatsAppService;
    expect($service->isRegistered('+1234567'))->toBeFalse();
});

it('returns false for too long phone number', function () {
    $service = new WhatsAppService;
    expect($service->isRegistered('+1234567890123456'))->toBeFalse();
});

it('validates E.164 format when GreenAPI is not configured', function () {
    config(['services.greenapi.instance_id' => null, 'services.greenapi.token' => null]);

    $service = new WhatsAppService;
    expect($service->isRegistered('+21650123456'))->toBeTrue()
        ->and($service->isRegistered('+1234567'))->toBeFalse();
});

it('calls GreenAPI when configured and returns true', function () {
    config(['services.greenapi.instance_id' => 'test123', 'services.greenapi.token' => 'abc']);

    Http::fake([
        'api.green-api.com/*' => Http::response(['existsWhatsapp' => true], 200),
    ]);

    $service = new WhatsAppService;
    Cache::flush();

    expect($service->isRegistered('+21650123456'))->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.green-api.com/waInstanceTest123/checkWhatsapp/abc'
            && $request['phoneNumber'] === '21650123456';
    });
});

it('returns false when GreenAPI returns existsWhatsapp as false', function () {
    config(['services.greenapi.instance_id' => 'test123', 'services.greenapi.token' => 'abc']);

    Http::fake([
        'api.green-api.com/*' => Http::response(['existsWhatsapp' => false], 200),
    ]);

    $service = new WhatsAppService;
    Cache::flush();

    expect($service->isRegistered('+21650123456'))->toBeFalse();
});

it('returns false when GreenAPI request fails', function () {
    config(['services.greenapi.instance_id' => 'test123', 'services.greenapi.token' => 'abc']);

    Http::fake([
        'api.green-api.com/*' => Http::response(null, 500),
    ]);

    $service = new WhatsAppService;
    Cache::flush();

    expect($service->isRegistered('+21650123456'))->toBeFalse();
});

it('caches the result for 7 days', function () {
    config(['services.greenapi.instance_id' => null, 'services.greenapi.token' => null]);

    $service = new WhatsAppService;
    Cache::flush();

    $service->isRegistered('+21650123456');

    expect(Cache::has('wa_reg_+21650123456'))->toBeTrue();
});

it('clears cache for a phone number', function () {
    $service = new WhatsAppService;
    Cache::put('wa_reg_+21650123456', true, now()->addDays(7));

    $service->forgetCache('+21650123456');

    expect(Cache::has('wa_reg_+21650123456'))->toBeFalse();
});

it('normalizes phone numbers correctly', function () {
    $service = new WhatsAppService;

    expect($service->normalize('+216 50 123 456'))->toBe('+21650123456')
        ->and($service->normalize('21650123456'))->toBeNull()
        ->and($service->normalize('+216-50-123-456'))->toBe('+21650123456')
        ->and($service->normalize(null))->toBeNull();
});
