<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * CAPTCHA verification service using a self-hosted CAP (CAPTCHA Alternative Provider).
 *
 * Communicates with an internal CAP service to verify user-submitted tokens.
 * In local environments, verification is bypassed when credentials are missing.
 */
class CapService
{
    protected string $siteKey;

    protected string $secretKey;

    protected string $internalUrl;

    public function __construct()
    {
        $this->siteKey = config('services.cap.site_key') ?? '';
        $this->secretKey = config('services.cap.secret_key') ?? '';
        $this->internalUrl = config('services.cap.internal_url') ?? 'http://cap:3000';
    }

    /**
     * Check whether CAPTCHA credentials are configured.
     */
    public function isConfigured(): bool
    {
        return filled($this->siteKey) && filled($this->secretKey);
    }

    /**
     * Verify a CAPTCHA token against the internal CAP service.
     * In local and testing environments, returns true if CAP is not configured.
     *
     * @throws RuntimeException When outside local/testing and credentials are missing.
     */
    public function verifyToken(?string $token): bool
    {
        if (! $this->isConfigured()) {
            // "testing" is bypassed alongside "local" so the suite never depends
            // on a reachable cap container. phpunit.xml blanks the keys to get
            // here; without this branch every form submission test would throw.
            if (app()->environment('local', 'testing')) {
                logger()->warning('CAPTCHA verification bypassed: CAP_SITE_KEY or CAP_SECRET_KEY not configured.');

                return true;
            }

            throw new RuntimeException('CAPTCHA is not configured. Set CAP_SITE_KEY and CAP_SECRET_KEY.');
        }

        if (blank($token)) {
            return false;
        }

        try {
            // Cap standalone's /siteverify expects { secret, response } where "response"
            // is the widget token (format "siteKey:id:sig") and "secret" is verified
            // against the site key's stored argon2 hash. See cap src/siteverify.js.
            $response = Http::timeout(10)
                ->post("{$this->internalUrl}/{$this->siteKey}/siteverify", [
                    'secret' => $this->secretKey,
                    'response' => $token,
                ]);

            if (! $response->successful()) {
                logger()->warning('Cap verification request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (RuntimeException $e) {
            logger()->error('Cap verification exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
