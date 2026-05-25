<?php

namespace App\Filament\Resources\IntroEventRecords;

use App\Filament\Resources\IntroEventRecords\Pages\CreateIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordForm;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use BackedEnum;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntroEventRecordResource extends Resource
{
    protected static ?string $model = IntroEventRecord::class;

    protected static string|BackedEnum|null $navigationIcon = TablerIcon::CalendarEvent;

    protected static ?string $modelLabel = 'Intro Event';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Intro Events';

    protected static ?string $navigationLabel = 'Introduction Events ';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';


    protected static ?string $recordTitleAttribute = 'NIS Data';

    public static function form(Schema $schema): Schema
    {
        return IntroEventRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntroEventRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntroEventRecords::route('/'),
            'create' => CreateIntroEventRecord::route('/create'),
            'edit' => EditIntroEventRecord::route('/{record}/edit'),
        ];
    }
}
