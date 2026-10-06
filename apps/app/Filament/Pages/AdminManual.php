<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Literatures\LiteratureGuide;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * The administration manual for super_admins and scientists, rendered from
 * resources/docs/admin-manual.md like the screen guides. The public site's
 * user manual is linked beside it (MamiasPanelProvider, "Help").
 */
class AdminManual extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-book';

    protected static string|null|\UnitEnum $navigationGroup = 'Help';

    protected static ?string $navigationLabel = 'Admin manual';

    protected static ?string $title = 'MAMIAS Administration Manual';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.admin-manual';

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Download PDF')
                ->icon('tabler-file-download')
                ->url(route('admin-manual.pdf')),
            Action::make('userManualPdf')
                ->label('User manual PDF')
                ->icon('tabler-file-download')
                ->color('gray')
                ->url(route('manual.pdf')),
        ];
    }

    public function getManualHtml(): string
    {
        return LiteratureGuide::html('admin-manual');
    }
}
