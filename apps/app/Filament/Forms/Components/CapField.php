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
     *
     * Stays relative by default ("/cap/{siteKey}/") so the widget resolves it
     * against whichever origin the page was served from, keeping the CAPTCHA
     * working under any hostname.
     */
    public function getApiEndpoint(): string
    {
        // `?:` rather than a config() default: the key is always defined by
        // config/services.php, so the default never fires — but an empty
        // CAP_PUBLIC_URL would otherwise yield a bare "/{siteKey}/".
        $publicUrl = rtrim((string) (config('services.cap.public_url') ?: '/cap'), '/');
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
