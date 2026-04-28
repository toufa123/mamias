<?php
    
    namespace App\Filament\Widgets;
    
    use Filament\Widgets\Widget;
    
    class MamiasInfoWidget extends Widget
    {
        protected string $view = 'filament.widgets.mamias-info-widget';
        
        public final function getViewData(): array
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
