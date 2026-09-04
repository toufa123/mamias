<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Filament\Resources\Taxons\Pages\Concerns\AppliesTaxonMatch;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Services\GbifService;
use App\Services\TaxonService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Page for editing taxons.
 */
class EditTaxon extends EditRecord
{
    use AppliesTaxonMatch;

    protected static string $resource = TaxonResource::class;

    protected function afterFill(): void
    {
        if (($this->data['catalogue_status'] ?? null) === Catalogue_Status::no_data_from_worms->value) {
            $this->mountAction('tryTaxonMatch');
        }
    }

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
        $isNoData = fn (): bool => ($this->data['catalogue_status'] ?? null) === Catalogue_Status::no_data_from_worms->value;

        return [
            Action::make('tryTaxonMatch')
                ->label('Try WoRMS Match')
                ->icon('tabler-wand')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Try WoRMS Taxon Match')
                ->modalDescription('This will search WoRMS using fuzzy matching for a potential match. If a match is found, you can apply it to update the scientific name.')
                ->modalSubmitActionLabel('Search')
                ->visible($isNoData)
                ->action(function (TaxonService $taxonService): void {
                    $taxonService->tryTaxonMatch(
                        fn ($key) => $this->data[$key] ?? null,
                        fn ($key, $value) => ($this->data[$key] = $value),
                    );
                }),
            Action::make('tryGbifMatch')
                ->label('Try GBIF Match')
                ->icon('tabler-world-search')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Search GBIF for Taxonomic Data')
                ->modalDescription('WoRMS had no data for this species. GBIF (Global Biodiversity Information Facility) covers a broader range of taxa and may have a match. The result will fill in the taxonomic classification fields.')
                ->modalSubmitActionLabel('Search GBIF')
                ->visible($isNoData)
                ->action(function (GbifService $gbifService): void {
                    $gbifService->tryGbifMatch(fn ($key) => $this->data[$key] ?? null);
                }),
            Action::make('unlockManualEntry')
                ->label('Enter Manually')
                ->icon('tabler-pencil')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Manual Data Entry')
                ->modalDescription('This will unlock all fields so you can type in the taxonomic data by hand. The status will be set to "Manual entry" to indicate the data was not sourced from WoRMS or GBIF.')
                ->modalSubmitActionLabel('Unlock Fields')
                ->visible($isNoData)
                ->action(function (): void {
                    $this->data['scientificname_editable'] = true;
                    $this->data['worms_status'] = Worms_Status::not_applicable->value;
                    $this->data['catalogue_status'] = Catalogue_Status::manual_entry->value;

                    Notification::make()
                        ->title('Manual Entry Unlocked')
                        ->body('All fields are now editable. Fill in the taxonomic data and save.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
