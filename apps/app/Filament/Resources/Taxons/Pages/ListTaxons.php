<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Enums\Catalogue_Status;
use App\Filament\Imports\TaxonImporter;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Filament\Widgets\ImportProgressWidget;
use App\Filament\Widgets\WormsFetchProgressWidget;
use App\Models\Taxon;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Page for listing taxons.
 */
class ListTaxons extends ListRecords
{
    protected static string $resource = TaxonResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ImportProgressWidget::class,
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
                ->badge(array_sum($countByStatus))
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
        ];

        foreach (Catalogue_Status::cases() as $status) {
            $value = $status->value;
            $tabs[$status->name] = Tab::make($status->getLabel())
                ->icon($status->getIcon())
                ->badgeColor($status->getColor())
                ->badge($countByStatus[$value] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('catalogue_status', $value)->withoutTrashed());
        }

        $qualityIssuesCount = Taxon::query()
            ->where(function (Builder $q) {
                $q->whereNull('fetched_at')
                    ->orWhere('fetched_at', '<', now()->subDays(90))
                    ->orWhere('catalogue_status', '!=', Catalogue_Status::checked_accepted->value);
            })
            ->count();

        $tabs['quality_issues'] = Tab::make('Quality Issues')
            ->icon('tabler-alert-triangle')
            ->badgeColor('warning')
            ->badge($qualityIssuesCount)
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
                $q->whereNull('fetched_at')
                    ->orWhere('fetched_at', '<', now()->subDays(90))
                    ->orWhere('catalogue_status', '!=', Catalogue_Status::checked_accepted->value);
            })->withoutTrashed());

        $duplicateNames = Taxon::query()
            ->select('scientificname')
            ->whereNotNull('scientificname')
            ->groupBy('scientificname')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('scientificname');

        $duplicatesCount = Taxon::query()
            ->whereIn('scientificname', $duplicateNames)
            ->count();

        $tabs['duplicates'] = Tab::make('Duplicates')
            ->icon('tabler-copy')
            ->badgeColor('danger')
            ->badge($duplicatesCount)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('scientificname', function ($sub) {
                $sub->select('scientificname')
                    ->from('taxas')
                    ->whereNull('deleted_at')
                    ->whereNotNull('scientificname')
                    ->groupBy('scientificname')
                    ->havingRaw('COUNT(*) > 1');
            })->withoutTrashed());

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

        ];
    }
}
