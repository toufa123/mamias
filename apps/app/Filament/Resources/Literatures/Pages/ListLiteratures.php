<?php

namespace App\Filament\Resources\Literatures\Pages;

use App\Filament\Resources\Literatures\LiteratureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiteratures extends ListRecords
{
    protected static string $resource = LiteratureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
