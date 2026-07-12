<?php

namespace App\Filament\Resources\Taxons;

use App\Filament\Resources\Taxons\Pages\CreateTaxon;
use App\Filament\Resources\Taxons\Pages\EditTaxon;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Filament\Resources\Taxons\Schemas\TaxonForm;
use App\Filament\Resources\Taxons\Schemas\TaxonInfolist;
use App\Filament\Resources\Taxons\Tables\TaxonTable;
use App\Models\Taxon;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Filament resource for managing NIS taxa (species catalogue).
 *
 * @extends \Filament\Resources\Resource
 *
 * @model App\Models\Taxon
 */
class TaxonResource extends Resource
{
    protected static ?string $model = Taxon::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-fish';

    protected static ?string $modelLabel = 'NIS Taxon';

    protected static ?string $pluralModelLabel = 'NIS Taxon';

    protected static ?string $navigationLabel = 'MAMIAS Catalogue ';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    /**
     * Configure the form schema for the resource.
     */
    public static function form(Schema $schema): Schema
    {
        return TaxonForm::configure($schema);
    }

    /**
     * Configure the table for the resource.
     */
    public static function table(Table $table): Table
    {
        return TaxonTable::configure($table);
    }

    /**
     * Configure the infolist schema for viewing a taxon.
     */
    public static function infolist(Schema $schema): Schema
    {
        return TaxonInfolist::configure($schema);
    }

    /**
     * Get the list of relation managers for the resource.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
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
            'index' => ListTaxons::route('/'),
            'create' => CreateTaxon::route('/create'),
            'edit' => EditTaxon::route('/{record}/edit'),
        ];
    }
}
