<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class CapField extends Field
{
    protected string $view = 'filament.forms.components.cap-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    public function getApiEndpoint(): string
    {
        $publicUrl = config('services.cap.public_url', 'http://localhost:3000');
        $siteKey = config('services.cap.site_key', '');

        return "{$publicUrl}/{$siteKey}/";
    }

    public function isConfigured(): bool
    {
        return filled(config('services.cap.site_key'));
    }
}
