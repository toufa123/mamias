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
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxonResource extends Resource
{
    protected static ?string $model = Taxon::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-fish';

    protected static ?string $modelLabel = 'NIS Taxon';

    protected static ?string $pluralModelLabel = 'NIS Taxon';

    protected static ?string $navigationLabel = 'MAMIAS Catalogue ';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';


    public static function form(Schema $schema): Schema
    {
        return TaxonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxonTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxonInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
            'index' => ListTaxons::route('/'),
            'create' => CreateTaxon::route('/create'),
            'edit' => EditTaxon::route('/{record}/edit'),
        ];
    }
}
