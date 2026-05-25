<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CapField;
use App\Filament\Forms\Components\HoneypotField;
use App\Services\CapService;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    use HasCustomLayout, UsesSpamProtection;

    public HoneypotData $honeypotData;

    public ?string $cap_token = null;

    public function mount(): void
    {
        parent::mount();

        if (! isset($this->honeypotData)) {
            $this->honeypotData = new HoneypotData;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        $this->getTitleComponent()->columnSpan(1),
                        $this->getFirstNameComponent()->columnSpan(1),
                        $this->getLastNameComponent()->columnSpan(1),
                        $this->getEmailFormComponent()->columnSpan(3),
                        $this->getCountryComponent()->columnSpan(3),
                        $this->getPasswordFormComponent()
                            ->autocomplete(false)
                            ->columnSpan(3),
                        $this->getPasswordConfirmationFormComponent()->columnSpan(3),
                        HoneypotField::make('honeypotData')
                            ->hidden(),
                        CapField::make('cap_token')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getTitleComponent(): Select
    {
        return Select::make('title')
            ->options([
                'Mr' => 'Mr',
                'Mrs' => 'Mrs',
                'Ms' => 'Ms',
                'Dr' => 'Dr',
                'Prof' => 'Prof',
            ])
            ->default('Mr')
            ->required()
            ->label('Title');
    }

    protected function getFirstNameComponent(): TextInput
    {
        return TextInput::make('first_name')
            ->label('First Name')
            ->autofocus()
            ->required();
    }

    protected function getLastNameComponent(): TextInput
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->required();
    }

    protected function getCountryComponent(): Select
    {
        return CountrySelect::make('country')
            ->displayFlags(true)
            ->required();
    }

    public function getRedirectUrl(): string
    {
        return route('filament.mamias.auth.email-verification.prompt');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        if (! isset($this->honeypotData)) {
            $this->honeypotData = new HoneypotData;
        }

        $this->protectAgainstSpam();

        $this->validateCapToken($data['cap_token'] ?? null);

        unset($data['cap_token'], $data['honeypotData']);

        $user = $this->getUserModel()::create($data);

        Role::findOrCreate('user', 'web');
        $user->assignRole('user');

        return $user;
    }

    protected function validateCapToken(?string $token): void
    {
        if (! app(CapService::class)->verifyToken($token)) {
            throw ValidationException::withMessages([
                'cap_token' => __('CAPTCHA verification failed. Please try again.'),
            ]);
        }
    }
}
