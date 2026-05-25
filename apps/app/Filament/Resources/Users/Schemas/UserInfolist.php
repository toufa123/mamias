<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Nakanakaii\Countries\Countries;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2, 'xl' => 2])
            ->components([
                Section::make('Personal Details')
                    ->description('Identity and contact information.')
                    ->icon('tabler-id')
                    ->collapsible()
                    ->columnSpan(['default' => 1, 'md' => 1])
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 5])
                            ->schema([
                                ImageEntry::make('avatar_url')
                                    ->hiddenLabel()
                                    ->circular()
                                    ->size(40)
                                    ->columnSpan(1),
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('—')
                                    ->columnSpan(1),
                                TextEntry::make('first_name')
                                    ->label('First Name')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->placeholder('—')
                                    ->columnSpan(1),
                                TextEntry::make('last_name')
                                    ->label('Last Name')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->placeholder('—')
                                    ->columnSpan(1),
                                TextEntry::make('roles.name')
                                    ->label('Roles')
                                    ->badge()
                                    ->color('success')
                                    ->separator(',')
                                    ->placeholder('—')
                                    ->columnSpan(1),
                                TextEntry::make('_spacer')
                                    ->hiddenLabel()
                                    ->state('')
                                    ->columnSpan(1),
                                TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->icon(fn ($record,
                                    ) => $record?->has_whatsapp ? 'tabler-brand-whatsapp' : 'tabler-phone')
                                    ->iconColor(fn ($record) => $record?->has_whatsapp ? 'success' : 'gray')
                                    ->copyable()
                                    ->placeholder('—')
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Action::make('checkWhatsapp')
                                            ->icon('tabler-brand-whatsapp')
                                            ->tooltip('Check WhatsApp')
                                            ->color('gray')
                                            ->action(function ($record) {
                                                if (blank($record?->phone)) {
                                                    Notification::make()
                                                        ->title('No phone number')
                                                        ->warning()
                                                        ->send();

                                                    return;
                                                }

                                                $service = app(WhatsAppService::class);
                                                $service->forgetCache($record->phone);
                                                $hasWhatsApp = $service->isRegistered($record->phone);

                                                $record->update(['has_whatsapp' => $hasWhatsApp]);

                                                Notification::make()
                                                    ->title($hasWhatsApp ? 'WhatsApp detected' : 'Not on WhatsApp')
                                                    ->color($hasWhatsApp ? 'success' : 'warning')
                                                    ->icon($hasWhatsApp ? 'tabler-brand-whatsapp' : 'tabler-phone-off')
                                                    ->send();
                                            }),
                                    ),
                                TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('—')
                                    ->columnSpan(2)
                                    ->html()
                                    ->formatStateUsing(function ($state) {
                                        if (blank($state)) {
                                            return null;
                                        }

                                        $country = Countries::findByCode($state);

                                        if (! $country) {
                                            return e($state);
                                        }

                                        $src = asset('vendor/countries/flags/'.strtolower($country['code']).'.png');
                                        $flag = '<img src="'.e($src).'" alt="'.e($country['name']).' Flag" style="height:1.25em;display:inline-block;vertical-align:middle;margin-right:0.35rem;" />';

                                        return $flag.e($country['name']);
                                    }),
                            ]),
                    ]),

                Section::make('Account Information')
                    ->description('Login credentials and verification status.')
                    ->icon('tabler-user-circle')
                    ->collapsible()
                    ->columnSpan(['default' => 1, 'md' => 1])
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->icon('tabler-mail')
                                    ->copyable(),
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->icon('tabler-mail-check')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn ($state,
                                    ) => $state ? $state->format('d M Y H:i') : 'Not verified'),
                            ]),
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Member Since')
                                    ->icon('tabler-calendar-plus')
                                    ->dateTime('d M Y H:i'),
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->icon('tabler-calendar-event')
                                    ->dateTime('d M Y H:i'),
                            ]),
                    ]),

                Section::make('Professional Focus')
                    ->description('Area of expertise and geographic interest.')
                    ->icon('tabler-school')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('taxonomic_area')
                            ->label('Taxonomic Areas')
                            ->badge()
                            ->color('warning')
                            ->separator(',')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('bio')
                            ->label('Biography')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make('subregions')
                                    ->label('EcAp Subregion')
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('—'),
                                TextEntry::make('countries')
                                    ->label('Countries')
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
