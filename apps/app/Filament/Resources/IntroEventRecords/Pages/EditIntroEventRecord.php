<?php

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntroEventRecord extends EditRecord
{
    protected static string $resource = IntroEventRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
