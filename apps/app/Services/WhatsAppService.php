<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp number validation service via GreenAPI.
 *
 * Falls back to E.164 format validation when GreenAPI credentials
 * are missing or the API is unreachable.
 *
 * @see https://green-api.com/docs/api/service/CheckWhatsApp/
 */
class WhatsAppService
{
    /**
     * Check whether a phone number is registered on WhatsApp.
     *
     * Uses GreenAPI when credentials are configured, otherwise falls
     * back to basic E.164 format validation.
     *
     * @param  string|null  $phone  Raw phone number
     */
    public function isRegistered(?string $phone): bool
    {
        $clean = $this->normalize($phone);

        if ($clean === null) {
            return false;
        }

        return Cache::remember(
            "wa_reg_{$clean}",
            now()->addDays(7),
            function () use ($clean) {
                $instanceId = config('services.greenapi.instance_id');
                $token = config('services.greenapi.token');

                // Fallback to E.164 validation when GreenAPI is not configured
                if (blank($instanceId) || blank($token)) {
                    return $this->looksLikeValidE164($clean);
                }

                try {
                    $response = Http::timeout(10)
                        ->post(
                            "https://api.green-api.com/waInstance{$instanceId}/checkWhatsapp/{$token}",
                            ['phoneNumber' => ltrim($clean, '+')],
                        );

                    if (! $response->successful()) {
                        return false;
                    }

                    return $response->json('existsWhatsapp', false);
                } catch (\Throwable $e) {
                    logger()->error('WhatsApp check failed', [
                        'phone' => $clean,
                        'message' => $e->getMessage(),
                    ]);

                    return false;
                }
            },
        );
    }

    /**
     * Normalize a raw phone number into E.164 format.
     *
     *
     * @return string|null E.164 number (e.g. +21650123456) or null if invalid
     */
    public function normalize(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Keep only digits and the leading plus sign
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with a plus sign
        if (! str_starts_with($clean, '+')) {
            return null;
        }

        // Remove duplicate plus signs
        $clean = '+'.ltrim($clean, '+');

        // Must have at least country code (1-3 digits) + subscriber number
        $digitsOnly = preg_replace('/\D/', '', $clean);
        if (strlen($digitsOnly) < 8 || strlen($digitsOnly) > 15) {
            return null;
        }

        return $clean;
    }

    /**
     * Quick E.164 format sanity check.
     *
     * @param  string  $phone  Already-normalized number
     */
    protected function looksLikeValidE164(string $phone): bool
    {
        // E.164: + followed by 8-15 digits
        return (bool) preg_match('/^\+\d{8,15}$/', $phone);
    }

    /**
     * Clear the cache for a specific phone number.
     */
    public function forgetCache(?string $phone): void
    {
        $clean = $this->normalize($phone);
        if ($clean) {
            Cache::forget("wa_reg_{$clean}");
        }
    }
}
