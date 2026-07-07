<?php

namespace App\Filament\Resources\NisSuggestions;

use App\Filament\Resources\NisSuggestions\Pages\ListNisSuggestions;
use App\Filament\Resources\NisSuggestions\Pages\ViewNisSuggestion;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NisSuggestionResource extends Resource
{
    protected static ?string $model = NisSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-bulb';

    protected static ?string $modelLabel = 'Species Suggestion';

    protected static ?string $pluralModelLabel = 'Species Suggestions';

    protected static ?int $navigationSort = 4;

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    public static function table(Table $table): Table
    {
        return NisSuggestionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NisSuggestionInfolist::configure($schema);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNisSuggestions::route('/'),
            'view' => ViewNisSuggestion::route('/{record}'),
        ];
    }
}
