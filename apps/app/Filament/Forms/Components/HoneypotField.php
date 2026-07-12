<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\Honeypot\Honeypot;

/**
 * Custom Filament form field that renders a hidden honeypot spam
 * protection component using Spatie's Honeypot package.
 */
class HoneypotField extends Field
{
    protected string $view = 'filament.forms.components.honeypot';

    /**
     * Returns the randomised honeypot field name from the Honeypot service.
     */
    public function getHoneypotName(): string
    {
        return app(Honeypot::class)->nameFieldName();
    }

    /**
     * Returns the unrandomised honeypot field name from the Honeypot service.
     */
    public function getUnrandomizedName(): string
    {
        return app(Honeypot::class)->unrandomizedNameFieldName();
    }

    /**
     * Returns the valid-from field name from the Honeypot service.
     */
    public function getValidFromFieldName(): string
    {
        return app(Honeypot::class)->validFromFieldName();
    }

    /**
     * Returns the encrypted valid-from timestamp from the Honeypot service.
     */
    public function getValidFromTimestamp(): string
    {
        return app(Honeypot::class)->encryptedValidFrom();
    }

    /**
     * Returns whether the honeypot spam protection is enabled in config.
     */
    public function isEnabled(): bool
    {
        return (bool) config('honeypot.enabled', true);
    }
}
