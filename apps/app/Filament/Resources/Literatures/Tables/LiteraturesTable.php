<?php
    
    namespace App\Filament\Resources\Literatures\Tables;
    
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;
    use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
    use App\Enums\LiteratureType;
    
    class LiteraturesTable
    {
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getCodeColumn(),
                self::getShortRefColumn(),
                self::getDoiColumn(),
                self::getTypeColumn(),
                self::getFullRefColumn(),
                self::getLinkColumn(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getCodeColumn(): TextColumn
    {
        return TextColumn::make('code')
            ->label('Code')
            ->sortable()
            ->searchable();
    }

    public static function getShortRefColumn(): TextColumn
    {
        return TextColumn::make('short_ref')
            ->label('Short Reference')
            ->sortable()
            ->searchable()
            ->wrap();
    }

    public static function getDoiColumn(): TextColumn
    {
        return TextColumn::make('doi')
            ->label('DOI')
            ->sortable()
            ->searchable()
            ->icon(TablerIcon::Link)
            ->iconPosition('before')
            ->url(fn ($state) => $state ? "https://doi.org/".$state : null)
            ->openUrlInNewTab()
            ->toggleable();
    }

    public static function getTypeColumn(): TextColumn
    {
        return TextColumn::make('type')
            ->label('Type')
            ->badge()
            ->sortable()
            ->toggleable();
    }

    public static function getFullRefColumn(): TextColumn
    {
        return TextColumn::make('full_ref')
            ->label('Full Reference')
            ->limit(100)
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true);
    }

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
