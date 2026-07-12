<?php

namespace App\Filament\Resources\Literatures\Tables;

use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures the Filament table for literature records.
 * Displays code, short reference, DOI, type, status, full reference,
 * file, and link columns.
 */
class LiteraturesTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getCodeColumn(),
                self::getShortRefColumn(),
                self::getDoiColumn(),
                self::getTypeColumn(),
                self::getStatusColumn(),
                self::getFullRefColumn(),
                self::getFileColumn(),
                self::getLinkColumn(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return TextColumn The code column, sortable and searchable.
     */
    public static function getCodeColumn(): TextColumn
    {
        return TextColumn::make('code')
            ->label('Code')
            ->sortable()
            ->searchable();
    }

    /**
     * @return TextColumn The short reference column, sortable, searchable, and wrapping.
     */
    public static function getShortRefColumn(): TextColumn
    {
        return TextColumn::make('short_ref')
            ->label('Short Reference')
            ->sortable()
            ->searchable()
            ->wrap();
    }

    /**
     * @return TextColumn The DOI column with an external link icon, sortable and searchable.
     */
    public static function getDoiColumn(): TextColumn
    {
        return TextColumn::make('doi')
            ->label('DOI')
            ->sortable()
            ->searchable()
            ->icon(TablerIcon::Link)
            ->iconPosition('before')
            ->url(fn ($state) => $state ? 'https://doi.org/'.$state : null)
            ->openUrlInNewTab()
            ->toggleable();
    }

    /**
     * @return TextColumn The type column as a badge, sortable and toggleable.
     */
    public static function getTypeColumn(): TextColumn
    {
        return TextColumn::make('type')
            ->label('Type')
            ->badge()
            ->sortable()
            ->toggleable();
    }

    /**
     * @return TextColumn The full reference column, limited to 100 characters and toggleable.
     */
    public static function getFullRefColumn(): TextColumn
    {
        return TextColumn::make('full_ref')
            ->label('Full Reference')
            ->limit(100)
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return TextColumn The file column with a document icon, toggleable.
     */
    public static function getFileColumn(): TextColumn
    {
        return TextColumn::make('file_path')
            ->label('File')
            ->icon(Heroicon::OutlinedDocument)
            ->toggleable();
    }

    /**
     * @return TextColumn The status column as a badge, sortable.
     */
    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable();
    }

    /**
     * @return TextColumn The link column with an external URL, limited to 50 characters and toggleable.
     */
    public static function getLinkColumn(): TextColumn
    {
        return TextColumn::make('link')
            ->label('Link')
            ->url(fn ($record) => $record->link)
            ->openUrlInNewTab()
            ->limit(50)
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
