<?php

namespace App\Filament\Resources\Literatures\Tables;

use App\Enums\LiteratureStatus;
use App\Models\Literature;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                self::getStatusColumn(),
                self::getCreatorColumn(),
                self::getFullRefColumn(),
                self::getLinkColumn(),
                self::getFileColumn(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LiteratureStatus::class),
            ])
            ->actions([
                Action::make('approve')
                    ->icon('tabler-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Literature $record) => $record->status === LiteratureStatus::PENDING)
                    ->action(fn (Literature $record) => $record->update(['status' => LiteratureStatus::APPROVED])),
                Action::make('reject')
                    ->icon('tabler-x')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Literature $record) => $record->status === LiteratureStatus::PENDING)
                    ->action(fn (Literature $record) => $record->update(['status' => LiteratureStatus::REJECTED])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->recordAction(ViewAction::class)
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
            ->url(fn ($state) => $state ? 'https://doi.org/'.$state : null)
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

    public static function getFileColumn(): TextColumn
    {
        return TextColumn::make('file_path')
            ->label('PDF')
            ->icon(fn ($state) => $state ? TablerIcon::FileTypePdf : null)
            ->formatStateUsing(fn ($state) => $state ? 'Download' : null)
            ->url(fn ($record) => $record->file_path ? asset('storage/'.$record->file_path) : null)
            ->openUrlInNewTab()
            ->toggleable();
    }

    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable();
    }

    public static function getCreatorColumn(): TextColumn
    {
        return TextColumn::make('creator.name')
            ->label('Submitted By')
            ->sortable()
            ->toggleable();
    }
}
