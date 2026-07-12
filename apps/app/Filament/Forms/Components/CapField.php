<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Custom Filament form field that renders a CAPTCHA widget using the
 * configured Cap service endpoint and site key.
 */
class CapField extends Field
{
    protected string $view = 'filament.forms.components.cap-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    /**
     * Builds the CAPTCHA API endpoint URL from the configured public
     * URL and site key.
     */
    public function getApiEndpoint(): string
    {
        $publicUrl = config('services.cap.public_url', 'http://localhost:3000');
        $siteKey = config('services.cap.site_key', '');

        return "{$publicUrl}/{$siteKey}/";
    }

    /**
     * Returns whether the CAPTCHA service has a site key configured.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.cap.site_key'));
    }
}
