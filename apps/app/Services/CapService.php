<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

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

    public function isConfigured(): bool
    {
        return filled($this->siteKey) && filled($this->secretKey);
    }

    public function verifyToken(?string $token): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
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
