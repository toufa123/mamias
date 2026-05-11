<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Enums\Catalogue_Status;
use App\Filament\Imports\TaxonImporter;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Filament\Widgets\WormsFetchProgressWidget;
use App\Models\Taxon;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Waad\FilamentImportWizard\Actions\ImportWizardAction;



class ListTaxons extends ListRecords
{
    protected static string $resource = TaxonResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WormsFetchProgressWidget::class,
        ];
    }

    protected function getListeners(): array
    {
        return [
            'worms-fetch-completed' => '$refresh',
            'easin-fetch-completed' => '$refresh',
            'import-completed' => '$refresh',
            'databaseNotificationsUpdated' => '$refresh',
        ];
    }

    public function getDefaultTab(): string|int|null
    {
        return 'checked_accepted';
    }

    public function getTabs(): array
    {
        $countByStatus = $this->getCatalogueStatusCounts();
        $trashedCount = Taxon::onlyTrashed()->count();

        $tabs = [
            'all' => Tab::make('All')
                ->icon('tabler-list')
                ->badge(array_sum($countByStatus)),
        ];

        foreach (Catalogue_Status::cases() as $status) {
            $value = $status->value;
            $tabs[$status->name] = Tab::make($status->getLabel())
                ->icon($status->getIcon())
                ->badgeColor($status->getColor())
                ->badge($countByStatus[$value] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('catalogue_status', $value));
        }

        $tabs['trashed'] = Tab::make('Trashed')
            ->icon('tabler-trash')
            ->badgeColor('danger')
            ->badge($trashedCount)
            ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed());

        return $tabs;
    }

    protected function getCatalogueStatusCounts(): array
    {
        return Taxon::query()
            ->selectRaw('catalogue_status, COUNT(*) as total')
            ->groupBy('catalogue_status')
            ->pluck('total', 'catalogue_status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(TaxonImporter::class)
                ->chunkSize(100),
            ImportWizardAction::make()
                ->label('Import Excel')
                ->icon('tabler-file-spreadsheet')
                ->color('success')
                ->forModel(Taxon::class)
                ->chunkSize(500)
                ->enableUpsert()
                ->upsertKeys(['scientificname']),
        ];
    }
}
