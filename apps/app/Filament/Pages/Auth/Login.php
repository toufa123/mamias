<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CapField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use App\Support\FilamentAuthRedirect;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Custom login page with CAPTCHA, redirect logic for authenticated users,
 * and role-based post-login redirection.
 */
class Login extends BaseLogin
{
    use HasCustomLayout, ValidatesCapToken;

    public ?string $cap_token = null;

    /**
     * Redirects already-authenticated users to the role-based URL,
     * otherwise proceeds with the standard login mount logic.
     */
    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect($this->getRedirectUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

    /**
     * @param  Schema  $schema  The Filament schema instance.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                CapField::make('cap_token')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Validates the CAPTCHA token before delegating to the parent
     * authentication logic.
     */
    public function authenticate(): ?LoginResponseContract
    {
        // The parent applies its own per-IP limit, but only after this method
        // has already run validateCapToken() — which is an outbound HTTP call
        // with a 10s timeout. Gate that call behind its own bucket first; a
        // separate method key keeps an attempt from being counted twice.
        try {
            $this->rateLimit(5, method: 'cap');
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $email = (string) ($data['email'] ?? '');

        if ($this->isLoginRateLimited($email)) {
            return null;
        }

        $this->validateCapToken($data['cap_token'] ?? null);

        $response = parent::authenticate();

        // Only a completed sign-in returns a response; an MFA challenge returns
        // null and keeps its attempts on the counter.
        if ($response !== null) {
            RateLimiter::clear($this->loginRateLimitKey($email));
        }

        return $response;
    }

    /**
     * Per-account attempt ceiling, mirroring Filament's own registration
     * limiter. Both limiters the parent provides are keyed on IP alone, so
     * without this one account can be attacked from unlimited addresses.
     */
    protected function isLoginRateLimited(string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $key = $this->loginRateLimitKey($email);

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'authenticate',
                request()->ip(),
                RateLimiter::availableIn($key),
            ))?->send();

            return true;
        }

        RateLimiter::hit($key);

        return false;
    }

    protected function loginRateLimitKey(string $email): string
    {
        return 'mamias-login:'.sha1(Str::lower($email));
    }

    /**
     * This is the site's only sign-in, not just the panel's: /login redirects
     * here. The parent refuses anyone who cannot enter the panel, which locked
     * out every public account outside local. Panel pages still enforce
     * canAccessPanel(), and FilamentAuthRedirect sends everyone else to '/'.
     */
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        return true;
    }

    protected function getRedirectUrl(): string
    {
        return FilamentAuthRedirect::for(auth()->user());
    }
}
