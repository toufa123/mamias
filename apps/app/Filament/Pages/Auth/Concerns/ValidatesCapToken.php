<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth\Concerns;

use App\Services\CapService;
use Illuminate\Validation\ValidationException;

trait ValidatesCapToken
{
    protected function validateCapToken(?string $token): void
    {
        if (! app(CapService::class)->verifyToken($token)) {
            throw ValidationException::withMessages([
                'cap_token' => __('CAPTCHA verification failed. Please try again.'),
            ]);
        }
    }
}
