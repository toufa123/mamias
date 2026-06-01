<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Filament\Resources\Taxons\Pages\Concerns\AppliesTaxonMatch;
use App\Filament\Resources\Taxons\TaxonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxon extends EditRecord
{
    use AppliesTaxonMatch;

    protected static string $resource = TaxonResource::class;

    protected function onTaxonMatchApplied(string $matchedName): void
    {
        $this->data['scientificname_editable'] = true;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
