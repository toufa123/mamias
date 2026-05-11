<?php
    
    namespace App\Filament\Resources\Users\Tables;
    
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteAction;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Actions\ViewAction;
    use Filament\Tables\Columns\IconColumn;
    use Filament\Tables\Columns\ImageColumn;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Enums\ColumnManagerLayout;
    use Filament\Tables\Table;
    use Nakanakaii\FilamentCountries\Tables\Columns\CountryColumn;
    use Nakanakaii\FilamentCountries\Tables\Filters\CountryFilter;
    
    class UsersTable
    {
        public static function configure(Table $table): Table
        {
            return $table
                ->striped()
                ->columnManagerLayout(ColumnManagerLayout::Modal)
                ->modifyQueryUsing(fn ($query) => $query->with('roles'))
                ->columns([
                    // add rowIndex
                    TextColumn::make('ID')
                        ->rowIndex()
                        ->label('No.'),
                    ImageColumn::make('avatar_url')
                        ->label('Avatar')
                        ->circular()
                        ->getStateUsing(function ($record) {
                            $firstName = trim((string) ($record->first_name ?? ''));
                            $lastName = trim((string) ($record->last_name ?? ''));
                            if ($firstName === '' && $lastName === '') {
                                $firstName = 'John';
                                $lastName = 'Doe';
                            }
                            $name = trim($firstName.' '.$lastName);
                            
                            return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=01B3ED&color=FFFFFF&bold=true';
                        }),
                    TextColumn::make('title')
                        ->searchable()->sortable(),
                    TextColumn::make('first_name')
                        ->searchable()->sortable(),
                    TextColumn::make('last_name')
                        ->searchable()->sortable(),
                    //                TextColumn::make('name')
                    //                    ->searchable(),
                    TextColumn::make('email')
                        ->label('Email address')
                        ->searchable(),
                    TextColumn::make('roles')
                        ->label('Roles')
                        ->getStateUsing(function ($record) {
                            return $record->roles->pluck('name')->join(', ');
                        })
                        ->badge()
                        ->searchable(),
                    TextColumn::make('phone')
                        ->label('Phone')
                        ->searchable()
                        ->url(fn($record) => $record->phone ? 'tel:'.$record->phone : null)
                        ->icon('tabler-phone')
                        ->iconColor('gray'),
                    IconColumn::make('has_whatsapp')
                        ->label('WhatsApp')
                        ->icon('tabler-brand-whatsapp')
                        ->color(fn($state) => $state ? 'success' : 'gray')
                        ->url(fn($record) => $record->has_whatsapp && $record->phone
                            ? 'https://wa.me/'.preg_replace('/\D/', '', $record->phone)
                            : null)
                        ->openUrlInNewTab()
                        ->tooltip(fn($record) => $record->has_whatsapp ? 'Open WhatsApp' : 'Not on WhatsApp'),
                    CountryColumn::make('country')
                        ->displayFlags(true) // Show or hide the flag (default: true)
                        ->hideName(false)    // Show or hide the country name (default: false)
                        ->imageFlags()       // Force this specific column to use Image flags instead of Emojis
                        ->searchable()->sortable(),
                    TextColumn::make('email_verified_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('deleted_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    CountryFilter::make('country'),
                    // ->displayFlags(true) // Show or hide the flag in the dropdown options (default: true)
                    // ->imageFlags()
                ])
                ->recordActions([
                    ViewAction::make()
                        ->modalWidth('6xl')
                        ->modalHeading(fn($record,
                        ) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ? : 'User'),
                    EditAction::make()
                        ->modalWidth('3xl'),
                    DeleteAction::make()
                        ->modalWidth('3xl'),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ]);
        }
    }
