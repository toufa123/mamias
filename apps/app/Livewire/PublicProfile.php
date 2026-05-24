<?php

namespace App\Livewire;

use App\Enums\Subregion;
use App\Services\WhatsAppService;
use App\Services\WormsService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;
use Nakanakaii\FilamentCountries\Forms\Components\PhoneInput;

class PublicProfile extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public bool $isEditing = false;

    public function mount(): void
    {
        $this->form->fill(auth()->user()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(auth()->user())
            ->disabled(fn () => ! $this->isEditing)
            ->components([
                $this->getPersonalDetailsSection(),
                $this->getAccountInformationSection(),
                $this->getProfessionalFocusSection(),
            ]);
    }

    private function getPersonalDetailsSection(): Section
    {
        return Section::make('Personal Details')
            ->description('Identity and contact information.')
            ->icon('tabler-id')
            ->compact()
            ->collapsible()
            ->schema([
                Grid::make(12)->schema([
                    $this->getTitleSelect(),
                    $this->getFirstNameInput(),
                    $this->getLastNameInput(),
                    $this->getPhoneInput(),
                    $this->getCountrySelect(),
                ]),
                Hidden::make('has_whatsapp'),
            ]);
    }

    private function getTitleSelect()
    {
        return Select::make('title')
            ->label('Title')
            ->options(['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms', 'Dr' => 'Dr', 'Prof' => 'Prof'])
            ->default('Mr')
            ->native(false)
            ->required()
            ->columnSpan(['default' => 12, 'sm' => 2]);
    }

    private function getFirstNameInput()
    {
        return TextInput::make('first_name')
            ->label('First Name')
            ->prefixIcon('tabler-user')
            ->placeholder('Jane')
            ->required()
            ->maxLength(255)
            ->columnSpan(['default' => 12, 'sm' => 2]);
    }

    private function getLastNameInput()
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->prefixIcon('tabler-user')
            ->placeholder('Doe')
            ->required()
            ->maxLength(255)
            ->columnSpan(['default' => 12, 'sm' => 3]);
    }

    private function getPhoneInput()
    {
        return PhoneInput::make('phone')
            ->label('Phone Number')
            ->placeholder('+xxx xxx xxx xxx')
            ->tel()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(fn (Set $set, ?string $state) => $this->handlePhoneWhatsAppCheck($set, $state))
            ->hintIcon('tabler-brand-whatsapp', tooltip: 'Include country code (e.g., +1). Green = WhatsApp registered.')
            ->hintColor(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
            ->suffixAction($this->getWhatsAppCheckAction())
            ->columnSpan(['default' => 12, 'sm' => 3]);
    }

    private function getCountrySelect()
    {
        return CountrySelect::make('country')
            ->label('Country')
            ->displayFlags(true)
            ->searchable()
            ->placeholder('Select your country')
            ->columnSpan(['default' => 12, 'sm' => 2]);
    }

    private function getWhatsAppCheckAction(): Action
    {
        return Action::make('checkWhatsapp')
            ->icon('tabler-brand-whatsapp')
            ->color(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
            ->tooltip('Check WhatsApp registration')
            ->action(function (Get $get, Set $set) {
                $phone = $get('phone');
                if (blank($phone)) {
                    return;
                }
                $this->handlePhoneWhatsAppCheck($set, $phone);
            });
    }

    private function handlePhoneWhatsAppCheck(Set $set, ?string $phone): void
    {
        if (blank($phone)) {
            $set('has_whatsapp', false);

            return;
        }

        $service = app(WhatsAppService::class);
        $service->forgetCache($phone);
        $set('has_whatsapp', $service->isRegistered($phone));
    }

    private function getAccountInformationSection(): Section
    {
        return Section::make('Account Information')
            ->description('Login credentials.')
            ->icon('tabler-user-circle')
            ->compact()
            ->collapsible()
            ->schema([
                Grid::make(2)->schema([
                    $this->getEmailInput(),
                    $this->getCurrentPasswordInput(),
                    $this->getPasswordInput(),
                ]),
            ]);
    }

    private function getEmailInput()
    {
        return TextInput::make('email')
            ->label('Email Address')
            ->prefixIcon('tabler-mail')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->placeholder('name@example.com');
    }

    private function getCurrentPasswordInput()
    {
        return TextInput::make('current_password')
            ->label('Current Password')
            ->password()
            ->revealable()
            ->prefixIcon('tabler-lock-open')
            ->placeholder('Required when changing password.')
            ->dehydrated(false)
            ->maxLength(255)
            ->visible(fn () => true);
    }

    private function getPasswordInput()
    {
        return TextInput::make('password')
            ->label('New Password')
            ->password()
            ->revealable()
            ->prefixIcon('tabler-lock')
            ->placeholder('Leave blank to keep current password.')
            ->dehydrated(fn ($state) => filled($state))
            ->maxLength(255)
            ->rule('required_with:current_password');
    }

    private function getProfessionalFocusSection(): Section
    {
        return Section::make('Professional Focus')
            ->description('Area of expertise and geographic interest.')
            ->icon('tabler-school')
            ->compact()
            ->collapsible()
            ->schema([
                Grid::make(2)->schema([
                    $this->getTaxonomicAreaSelect(),
                    $this->getBioInput(),
                    $this->getGeographicAreasFieldset(),
                ]),
            ])->columnSpanFull();
    }

    private function getTaxonomicAreaSelect()
    {
        return Select::make('taxonomic_area')
            ->label('Taxonomic Areas')
            ->multiple()
            ->searchable()
            ->options($this->getTaxonomicAreaOptions())
            ->placeholder('Select taxonomic areas...')
            ->helperText('Phyla of scientific interest (sourced from WoRMS).')
            ->columnSpanFull();
    }

    private function getTaxonomicAreaOptions(): array
    {
        try {
            return app(WormsService::class)->getPhyla();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getBioInput()
    {
        return Textarea::make('bio')
            ->label('Biography')
            ->rows(3)
            ->placeholder('A short professional biography...')
            ->maxLength(1000)
            ->autosize()
            ->columnSpanFull();
    }

    private function getGeographicAreasFieldset(): Fieldset
    {
        return Fieldset::make('Geographic Areas')
            ->schema([
                Select::make('subregions')
                    ->label('EcAp Subregions')
                    ->multiple()
                    ->options($this->getSubregionOptions())
                    ->searchable()
                    ->placeholder('Select subregions...'),
                CountrySelect::make('countries')
                    ->label('Countries of Interest')
                    ->displayFlags(true)
                    ->searchable()
                    ->multiple()
                    ->placeholder('Select countries...'),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    private function getSubregionOptions(): array
    {
        return collect(Subregion::cases())
            ->mapWithKeys(fn (Subregion $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if (filled($state['password'] ?? null)) {
            $this->form->validate(['current_password' => ['required', 'current_password']]);
        }

        $data = array_filter($state, fn ($key) => $key !== 'current_password', ARRAY_FILTER_USE_KEY);
        auth()->user()->update($data);
        $this->isEditing = false;

        Notification::make()
            ->success()
            ->title('Profile updated successfully.')
            ->send();
    }

    public function toggleEdit(): void
    {
        if ($this->isEditing) {
            $this->form->fill(auth()->user()->attributesToArray());
        }
        $this->isEditing = ! $this->isEditing;
    }

    public function render(): View
    {
        return view('livewire.public-profile')->extends('app')->section('content');
    }
}
