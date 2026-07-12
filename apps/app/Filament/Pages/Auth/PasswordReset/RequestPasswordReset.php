<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth\PasswordReset;

use App\Filament\Forms\Components\CapField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Schemas\Schema;

/**
 * Custom password reset request page that adds CAPTCHA validation to
 * the base Filament request flow.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    use HasCustomLayout, ValidatesCapToken;

    public ?string $cap_token = null;

    /**
     * @param  Schema  $schema  The Filament schema instance.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                CapField::make('cap_token')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Validates the CAPTCHA token before delegating to the parent
     * password reset request logic.
     */
    public function request(): void
    {
        $data = $this->form->getState();

        $this->validateCapToken($data['cap_token'] ?? null);

        parent::request();
    }
}
