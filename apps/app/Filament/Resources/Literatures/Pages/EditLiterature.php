<?php

namespace App\Filament\Resources\Literatures\Pages;

use App\Filament\Resources\Literatures\LiteratureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Page for editing literatures.
 */
class EditLiterature extends EditRecord
{
    protected static string $resource = LiteratureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
