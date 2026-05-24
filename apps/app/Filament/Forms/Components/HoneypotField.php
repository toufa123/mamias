<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\Honeypot\Honeypot;

class HoneypotField extends Field
{
    protected string $view = 'filament.forms.components.honeypot';

    public function getHoneypotName(): string
    {
        return app(Honeypot::class)->nameFieldName();
    }

    public function getUnrandomizedName(): string
    {
        return app(Honeypot::class)->unrandomizedNameFieldName();
    }

    public function getValidFromFieldName(): string
    {
        return app(Honeypot::class)->validFromFieldName();
    }

    public function getValidFromTimestamp(): string
    {
        return app(Honeypot::class)->encryptedValidFrom();
    }

    public function isEnabled(): bool
    {
        return (bool) config('honeypot.enabled', true);
    }
}
