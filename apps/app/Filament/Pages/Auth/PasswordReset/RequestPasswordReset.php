<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth\PasswordReset;

use App\Filament\Forms\Components\CapField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Schemas\Schema;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    use HasCustomLayout, ValidatesCapToken;

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

        $this->validateCapToken($data['cap_token'] ?? null);

        parent::request();
    }
}
