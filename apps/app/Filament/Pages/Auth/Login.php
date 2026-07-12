<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CapField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use App\Support\FilamentAuthRedirect;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;

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
        $data = $this->form->getState();

        $this->validateCapToken($data['cap_token'] ?? null);

        return parent::authenticate();
    }

    protected function getRedirectUrl(): string
    {
        return FilamentAuthRedirect::for(auth()->user());
    }
}
