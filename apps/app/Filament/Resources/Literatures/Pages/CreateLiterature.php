<?php

namespace App\Filament\Resources\Literatures\Pages;

use App\Filament\Resources\Literatures\LiteratureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLiterature extends CreateRecord
{
    protected static string $resource = LiteratureResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
    
}
