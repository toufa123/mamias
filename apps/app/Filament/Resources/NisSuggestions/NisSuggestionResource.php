<?php

namespace App\Filament\Resources\NisSuggestions;

use App\Filament\Resources\NisSuggestions\Pages\ListNisSuggestions;
use App\Filament\Resources\NisSuggestions\Pages\ViewNisSuggestion;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Filament resource for managing NIS species suggestions from users.
 *
 * @extends \Filament\Resources\Resource
 *
 * @model App\Models\NisSuggestion
 */
class NisSuggestionResource extends Resource
{
    protected static ?string $model = NisSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-bulb';

    protected static ?string $modelLabel = 'Species Suggestion';

    protected static ?string $pluralModelLabel = 'Species Suggestions';

    protected static ?int $navigationSort = 4;

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    /**
     * Configure the table for the resource.
     */
    public static function table(Table $table): Table
    {
        return NisSuggestionsTable::configure($table);
    }

    /**
     * Configure the infolist schema for viewing a suggestion.
     */
    public static function infolist(Schema $schema): Schema
    {
        return NisSuggestionInfolist::configure($schema);
    }

    /**
     * Get the route binding query, including soft-deleted records.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get the page routes for the resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListNisSuggestions::route('/'),
            'view' => ViewNisSuggestion::route('/{record}'),
        ];
    }
}
