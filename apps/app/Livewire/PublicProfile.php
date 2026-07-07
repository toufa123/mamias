<?php

namespace App\Livewire;

use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
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
                Section::make('Personal Details')
                    ->description('Identity and contact information.')
                    ->icon('tabler-id')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('title')
                                    ->label('Title')
                                    ->options([
                                        'Mr' => 'Mr',
                                        'Mrs' => 'Mrs',
                                        'Ms' => 'Ms',
                                        'Dr' => 'Dr',
                                        'Prof' => 'Prof',
                                    ])
                                    ->default('Mr')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 2,
                                    ]),
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Jane')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 2,
                                    ]),
                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Doe')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 3,
                                    ]),
                                PhoneInput::make('phone')
                                    ->label('Phone Number')
                                    ->placeholder('+xxx xxx xxx xxx')
                                    ->tel()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Set $set) {
                                        if (blank($state)) {
                                            $set('has_whatsapp', false);

                                            return;
                                        }

                                        $service = app(WhatsAppService::class);
                                        $service->forgetCache($state);
                                        $set('has_whatsapp', $service->isRegistered($state));
                                    })
                                    ->hintIcon(
                                        'tabler-brand-whatsapp',
                                        tooltip: 'Include country code (e.g., +1). Green = WhatsApp registered.',
                                    )
                                    ->hintColor(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
                                    ->suffixAction(
                                        Action::make('checkWhatsapp')
                                            ->icon('tabler-brand-whatsapp')
                                            ->color(fn (Get $get) => (bool) $get('has_whatsapp') ? 'success' : 'gray')
                                            ->tooltip('Check WhatsApp registration')
                                            ->action(function (Get $get, Set $set) {
                                                $phone = $get('phone');
                                                if (blank($phone)) {
                                                    return;
                                                }
                                                $service = app(WhatsAppService::class);
                                                $service->forgetCache($phone);
                                                $set('has_whatsapp', $service->isRegistered($phone));
                                            }),
                                    )
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 3,
                                    ]),
                                CountrySelectWithMedPriority::make('country')
                                    ->label('Country')
                                    ->displayFlags(true)
                                    ->searchable()
                                    ->placeholder('Select your country')
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 2,
                                    ]),
                            ]),
                        Hidden::make('has_whatsapp'),
                    ]),
                Section::make('Account Information')
                    ->description('Login credentials.')
                    ->icon('tabler-user-circle')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->prefixIcon('tabler-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('name@example.com'),
                                TextInput::make('password')
                                    ->label('New Password')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('tabler-lock')
                                    ->placeholder('Leave blank to keep current password.')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->maxLength(255),
                            ]),
                    ]),
                Section::make('Professional Focus')
                    ->description('Area of expertise and geographic interest.')
                    ->icon('tabler-school')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('taxonomic_area')
                                    ->label('Taxonomic Areas')
                                    ->multiple()
                                    ->searchable()
                                    ->options(function (): array {
                                        try {
                                            return app(WormsService::class)->getPhyla();
                                        } catch (\Throwable) {
                                            return [];
                                        }
                                    })
                                    ->placeholder('Select taxonomic areas...')
                                    ->helperText('Phyla of scientific interest (sourced from WoRMS).')
                                    ->columnSpanFull(),
                                Textarea::make('bio')
                                    ->label('Biography')
                                    ->rows(3)
                                    ->placeholder('A short professional biography...')
                                    ->maxLength(1000)
                                    ->autosize()
                                    ->columnSpanFull(),
                                Fieldset::make('Geographic Areas')
                                    ->schema([
                                        Select::make('subregions')
                                            ->label('EcAp Subregions')
                                            ->multiple()
                                            ->options(function (): array {
                                                return collect(Subregion::cases())
                                                    ->mapWithKeys(fn (Subregion $case) => [$case->value => $case->getLabel()])
                                                    ->all();
                                            })
                                            ->searchable()
                                            ->placeholder('Select subregions...'),
                                        CountrySelectWithMedPriority::make('countries')
                                            ->label('Countries of Interest')
                                            ->displayFlags(true)
                                            ->searchable()
                                            ->multiple()
                                            ->placeholder('Select countries...'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

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
        return view('livewire.public-profile')
            ->extends('app')
            ->section('content');
    }
}
