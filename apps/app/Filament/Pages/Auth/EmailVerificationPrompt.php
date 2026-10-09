<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Support\FilamentAuthRedirect;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

/**
 * Email verification prompt page using the custom auth UI enhancer layout
 * and no top bar.
 */
class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    use HasCustomLayout;

    protected bool $hasTopbar = false;

    /**
     * Filament sends guests and already-verified users to the panel home,
     * which 403s public users; route them like login/registration instead.
     *
     * With registration approval on there is no link to wait for: an
     * unapproved account is signed out and browses as a guest until a
     * super_admin verifies it.
     */
    public function mount(): void
    {
        $user = auth()->user();

        if ((! $user) || $this->getVerifiable()->hasVerifiedEmail()) {
            $this->redirect(FilamentAuthRedirect::for($user));

            return;
        }

        if (config('auth.registration_approval')) {
            Filament::auth()->logout();
            session()->regenerateToken();

            Notification::make()
                ->title(__('Awaiting approval'))
                ->body(__('Your account is awaiting approval by an administrator. You can sign in once it is approved.'))
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(Filament::getLoginUrl());
        }
    }
}
