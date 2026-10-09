<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Support\FilamentAuthRedirect;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

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

    public function getHeading(): string|Htmlable|null
    {
        return config('auth.registration_approval')
            ? __('Awaiting approval')
            : parent::getHeading();
    }

    /**
     * With registration approval on, no link was emailed, so there is
     * nothing to resend: explain the wait instead.
     */
    public function content(Schema $schema): Schema
    {
        if (! config('auth.registration_approval')) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                Text::make(__('Your account (:email) has been created and is waiting for an administrator to approve it.', [
                    'email' => $this->getVerifiable()->getEmailForVerification(),
                ])),
                Text::make(__('Once it is approved, reload this page or sign in again to continue.')),
            ]);
    }
}
