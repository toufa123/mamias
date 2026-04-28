<?php
    
    namespace App\Filament\Pages\Auth;
    
    use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
    use Filament\Auth\Pages\Register as BaseRegister;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\TextInput;
    use Filament\Schemas\Components\Grid;
    use Filament\Schemas\Schema;
    use Illuminate\Database\Eloquent\Model;
    use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;
    
    class Register extends BaseRegister
    {
        // protected string $view = 'filament.pages.auth.register';
        
        use HasCustomLayout;
        
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
            return
                CountrySelect::make('country')
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
            $user = parent::handleRegistration($data);
            
            $user->assignRole('user');
            
            return $user;
        }
    }
