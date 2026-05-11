<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Subregion;
use App\Services\WhatsAppService;
use App\Services\WormsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;
use Nakanakaii\FilamentCountries\Forms\Components\PhoneInput;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
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
                                    ->columnSpan(3),
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Jane')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->prefixIcon('tabler-user')
                                    ->placeholder('Doe')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(5),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Grid::make(1)
                                    ->schema([
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
                                            ->extraInputAttributes(fn (Get $get) => [
                                                'style' => (bool) $get('has_whatsapp') ? 'border: 2px solid #22c55e !important;' : '',
                                            ])
                                            ->hintIcon(
                                                'tabler-brand-whatsapp',
                                                tooltip: 'Include country code (e.g., +1 for USA). If the phone number has WhatsApp it will turn green',
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
                                            ),
                                        Hidden::make('has_whatsapp'),
                                    ]),
                                CountrySelect::make('country')
                                    ->label('Country')
                                    ->displayFlags(true)
                                    ->searchable()
                                    ->placeholder('Select your country'),
                                Select::make('roles')
                                    ->label('Roles?')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Assign one or more roles to this user.'),

                            ]),
                    ]),
                Section::make('Account Information')
                    ->description('Login credentials and verification status.')
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
                                    ->label('Password?')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('tabler-lock')
                                    ->placeholder('Leave blank to keep current password.')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn ($operation) => $operation === 'create')
                                    ->maxLength(255)
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Minimum 8 characters recommended.'),
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->prefixIcon('tabler-mail-check')
                                    ->native(false)
                                    ->seconds(false)
                                    ->columnSpanFull(),
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
                                    ->label('Taxonomic Areas?')
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
                                    ->hintIcon('heroicon-m-question-mark-circle',
                                        tooltip: 'Phyla of scientific interest (sourced from WoRMS).')
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
                                            ->label('EcAp Subregion?')
                                            ->multiple()
                                            ->options(function (): array {
                                                return collect(Subregion::cases())
                                                    ->mapWithKeys(fn (Subregion $case,
                                                    ) => [$case->value => $case->getLabel()])
                                                    ->all();
                                            })
                                            ->searchable()
                                            ->placeholder('Select subregions...')
                                            ->hintIcon('heroicon-m-question-mark-circle',
                                                tooltip: 'Select broad geographic regions of interest.')
                                            ->helperText('Select broad geographic regions of interest.'),
                                        CountrySelect::make('countries')
                                            ->label('Countries?')
                                            ->displayFlags(true)
                                            ->searchable()
                                            ->multiple()
                                            ->placeholder('Select countries...')
                                            ->hintIcon('heroicon-m-question-mark-circle',
                                                tooltip: 'Select specific countries of interest.')
                                            ->helperText('Select specific countries of interest.'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
