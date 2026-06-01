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

class Login extends BaseLogin
{
    use HasCustomLayout, ValidatesCapToken;

    public ?string $cap_token = null;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect($this->getRedirectUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

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
