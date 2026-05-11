<?php

namespace App\Filament\Resources\Literatures;

use App\Filament\Resources\Literatures\Pages\CreateLiterature;
use App\Filament\Resources\Literatures\Pages\EditLiterature;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use App\Models\Literature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LiteratureResource extends Resource
{
    protected static ?string $model = Literature::class;
    
    protected static string|BackedEnum|null $navigationIcon = 'tabler-book';
    
    protected static ?string $modelLabel = 'Literature';
    
    protected static ?string $pluralModelLabel = 'Literatures';
    
    protected static ?string $navigationLabel = 'Literatures';
    
    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';
    
    protected static ?string $recordTitleAttribute = 'short_ref';

    public static function form(Schema $schema): Schema
    {
        return LiteratureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LiteraturesTable::configure($table);
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
            'index' => ListLiteratures::route('/'),
            'create' => CreateLiterature::route('/create'),
            'edit' => EditLiterature::route('/{record}/edit'),
        ];
    }
}
