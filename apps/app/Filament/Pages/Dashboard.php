<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CatalogueEnvironmentChart;
use App\Filament\Widgets\CatalogueStatsWidget;
use App\Filament\Widgets\PhylumByKingdomChart;
use App\Filament\Widgets\SpeciesByKingdomChart;
use App\Filament\Widgets\SpeciesByPhylumChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('dashboard')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('MAMIAS Catalogue')
                            ->icon('tabler-fish')
                            ->schema([
                                Grid::make(4)->schema(
                                    $this->getWidgetsSchemaComponents([
                                        CatalogueStatsWidget::class,
                                    ])
                                ),
                                Grid::make(2)->schema([
                                    Livewire::make(SpeciesByKingdomChart::class),
                                    Livewire::make(SpeciesByPhylumChart::class),
                                ]),
                                Grid::make(2)->schema([
                                    Livewire::make(PhylumByKingdomChart::class),
                                    Livewire::make(CatalogueEnvironmentChart::class),
                                ]),
                            ]),
                        Tab::make('MAMIAS Data')
                            ->icon('tabler-database')
                            ->schema([

                            ]),
                    ]),
            ]);
    }
}
