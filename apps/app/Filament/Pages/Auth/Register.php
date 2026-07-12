<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CapField;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Forms\Components\HoneypotField;
use App\Filament\Pages\Auth\Concerns\ValidatesCapToken;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;
use Spatie\Permission\Models\Role;

/**
 * Custom registration page with CAPTCHA, honeypot spam protection,
 * title/name/country fields, and automatic user-role assignment.
 */
class Register extends BaseRegister
{
    use HasCustomLayout, UsesSpamProtection, ValidatesCapToken;

    public HoneypotData $honeypotData;

    public ?string $cap_token = null;

    /**
     * Initialises the honeypot data property and delegates to the
     * parent mount logic.
     */
    public function mount(): void
    {
        parent::mount();

        if (! isset($this->honeypotData)) {
            $this->honeypotData = new HoneypotData;
        }
    }

    /**
     * @param  Schema  $schema  The Filament schema instance.
     */
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
        return CountrySelectWithMedPriority::make('country')
            ->displayFlags(true)
            ->required();
    }

    /**
     * Returns the post-registration redirect URL to the email
     * verification prompt page.
     */
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
}
