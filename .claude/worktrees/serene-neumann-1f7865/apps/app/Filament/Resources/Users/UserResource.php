<?php
    
    namespace App\Filament\Resources\Users;
    
    use App\Filament\Resources\Users\Pages\CreateUser;
    use App\Filament\Resources\Users\Pages\EditUser;
    use App\Filament\Resources\Users\Pages\ListUsers;
    use App\Filament\Resources\Users\Schemas\UserForm;
    use App\Filament\Resources\Users\Tables\UsersTable;
    use App\Models\User;
    use BackedEnum;
    use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Table;
    
    class UserResource extends Resource
    {
        protected static ?string $model = User::class;
        
        protected static string|BackedEnum|null $navigationIcon = TablerIcon::Users;
        
        protected static ?string $modelLabel = 'User';
        
        protected static ?string $pluralModelLabel = 'Users';
        
        protected static ?string $recordTitleAttribute = 'Users';
        
        protected static ?string $navigationLabel = 'Users';
        
        protected static string|null|\UnitEnum $navigationGroup = 'User Management';
        
        public static function form(Schema $schema): Schema
        {
            return UserForm::configure($schema);
        }
        
        public static function table(Table $table): Table
        {
            return UsersTable::configure($table);
        }
        
        public static function getRelations(): array
        {
            return [
                //
            ];
        }
        
        public static function getPages(): array
        {
            return [
                'index' => ListUsers::route('/'),
                'create' => CreateUser::route('/create'),
                'edit' => EditUser::route('/{record}/edit'),
            ];
        }
    }
