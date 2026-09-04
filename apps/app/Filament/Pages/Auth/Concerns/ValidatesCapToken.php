<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth\Concerns;

use App\Services\CapService;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Trait that validates a CAPTCHA token against the CapService before allowing
 * form submission to proceed. Surfaces a visible danger notification (in addition
 * to a field error) when the "Verify you are human" challenge is missing or fails.
 */
trait ValidatesCapToken
{
    protected function validateCapToken(?string $token): void
    {
        $cap = app(CapService::class);

        // Challenge is active but the user has not completed it.
        if ($cap->isConfigured() && blank($token)) {
            Notification::make()
                ->title(__('Please verify that you are human'))
                ->body(__('Complete the "Verify you are human" challenge, then submit again.'))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.cap_token' => __('Please complete the human-verification challenge.'),
            ]);
        }

        // Token present (or bypassed in local/unconfigured) but verification failed.
        if (! $cap->verifyToken($token)) {
            Notification::make()
                ->title(__('Human verification failed'))
                ->body(__('We could not verify the challenge. Please try the "Verify you are human" step again.'))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.cap_token' => __('CAPTCHA verification failed. Please try again.'),
            ]);
        }
    }
}
