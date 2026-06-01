<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Filament\Resources\Taxons\Pages\Concerns\AppliesTaxonMatch;
use App\Filament\Resources\Taxons\TaxonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxon extends CreateRecord
{
    use AppliesTaxonMatch;

    protected static string $resource = TaxonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
