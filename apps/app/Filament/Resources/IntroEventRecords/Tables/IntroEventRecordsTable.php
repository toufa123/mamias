<?php

namespace App\Filament\Resources\IntroEventRecords\Tables;

use App\Models\IntroEventRecord;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntroEventRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('taxon.scientificname')
                    ->label('Taxon')
                    ->searchable(),
                TextColumn::make('first_introduction_year')
                    ->label('1st Year of Introduction')
                    ->numeric()
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
                TextColumn::make('Literature.source_type')
                    ->label('Citation')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                //                TextColumn::make('data_source_type')
                //                    ->badge()
                //                    ->searchable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_by')
                    ->numeric()
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordClasses(fn (IntroEventRecord $record): string => $record->needs_review
                ? 'bg-danger-50 dark:bg-danger-950 hover:bg-danger-100 dark:hover:bg-danger-900'
                : ''
            );
    }
}
