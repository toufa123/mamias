<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Info widget displaying MAMIAS application name, version, logo URL, and
 * related links on the dashboard.
 */
class MamiasInfoWidget extends Widget
{
    protected string $view = 'filament.widgets.mamias-info-widget';

    /**
     * @return array<string, string> Application metadata for the view.
     */
    final public function getViewData(): array
    {
        return [
            'appName' => 'MAMIAS',
            'version' => '1.0.0',
            'logoUrl' => asset('images/Logoweb.png'),
            'docUrl' => '',
            'gitUrl' => 'https://github.com/toufa123/mamias',
        ];
    }
}
