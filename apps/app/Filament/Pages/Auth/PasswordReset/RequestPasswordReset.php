<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth\PasswordReset;

use App\Filament\Forms\Components\CapField;
use App\Services\CapService;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    use HasCustomLayout;

    public ?string $cap_token = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                CapField::make('cap_token')
                    ->columnSpanFull(),
            ]);
    }

    public function request(): void
    {
        $data = $this->form->getState();

        if (! app(CapService::class)->verifyToken($data['cap_token'] ?? null)) {
            throw ValidationException::withMessages([
                'cap_token' => __('CAPTCHA verification failed. Please try again.'),
            ]);
        }

        parent::request();
    }
}
