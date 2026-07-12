<?php

namespace App\Filament\Resources\Literatures;

use App\Filament\Resources\Literatures\Pages\CreateLiterature;
use App\Filament\Resources\Literatures\Pages\EditLiterature;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use App\Models\Literature;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Filament resource for managing literature references.
 *
 * @extends \Filament\Resources\Resource
 *
 * @model App\Models\Literature
 */
class LiteratureResource extends Resource
{
    protected static ?string $model = Literature::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-book';

    protected static ?string $modelLabel = 'Literature';

    protected static ?string $pluralModelLabel = 'Literatures';

    protected static ?string $navigationLabel = 'Literatures';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    protected static ?string $recordTitleAttribute = 'short_ref';

    /**
     * Configure the form schema for the resource.
     */
    public static function form(Schema $schema): Schema
    {
        return LiteratureForm::configure($schema);
    }

    /**
     * Configure the table for the resource.
     */
    public static function table(Table $table): Table
    {
        return LiteraturesTable::configure($table);
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
     * Get the page routes for the resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLiteratures::route('/'),
            'create' => CreateLiterature::route('/create'),
            'edit' => EditLiterature::route('/{record}/edit'),
        ];
    }
}
