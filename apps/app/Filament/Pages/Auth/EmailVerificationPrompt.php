<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Support\FilamentAuthRedirect;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;

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
     */
    public function mount(): void
    {
        $user = auth()->user();

        if ((! $user) || $this->getVerifiable()->hasVerifiedEmail()) {
            $this->redirect(FilamentAuthRedirect::for($user));
        }
    }
}
