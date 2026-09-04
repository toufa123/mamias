<?php

namespace App\Filament\Resources\IntroEventRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use OccTherapist\AdvancedTableExportForFilament\Actions\TableExportQuickHeaderAction;

/**
 * Configures the Filament table for intro event records.
 * Displays taxon, introduction year, country, NIS status,
 * establishment status, EICAT impact, and citation columns.
 */
class IntroEventRecordsTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['taxon', 'literature']))
            ->columns([
                TextColumn::make('taxon.scientificname')
                    ->label('Taxon')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record): string => $state.($record?->taxon?->authority ? ' ('.$record->taxon->authority.')' : '')),
                TextColumn::make('first_introduction_year')
                    ->label('1st Year of Introduction')
                    ->numeric(thousandsSeparator: '')
                    ->sortable(),
                TextColumn::make('first_country')
                    ->label('1st Country of Introduction')
                    ->formatStateUsing(static function ($state): string {
                        if (blank($state)) {
                            return '-';
                        }

                        $countries = is_array($state) ? $state : [$state];

                        return implode(', ', array_filter($countries));
                    })
                    ->searchable(),
                TextColumn::make('nis_status')
                    ->label('NIS Status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('establishment_status')
                    ->label('Establishment Status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('literature.short_ref')
                    ->label('Citation')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
                //                TextColumn::make('data_source_type')
                //                    ->badge()
                //                    ->searchable(),
                TextColumn::make('created_by')
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_by')
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                TableExportQuickHeaderAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
