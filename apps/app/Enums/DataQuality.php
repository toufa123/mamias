<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Data quality rating for records in the catalogue.
 *
 * Provides a standardised quality assessment with Filament-compatible
 * color and icon indicators for UI display.
 */
enum DataQuality: string implements HasColor, HasIcon, HasLabel
{
    /** Not applicable — quality rating does not apply. */
    case NA = 'N/A';

    /** High quality — verified, reliable data. */
    case High = 'high';

    /** Medium quality — some uncertainty or incomplete metadata. */
    case Medium = 'medium';

    /** Low quality — unverified or uncertain data. */
    case Low = 'low';

    /**
     * Human-readable label for the data quality level.
     */
    public function getLabel(): ?string
    {
        return ucfirst($this->value);
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NA => 'gray',
            self::High => 'success',
            self::Medium => 'warning',
            self::Low => 'danger',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::NA => 'tabler-number-0-small',
            self::High => 'tabler-shield-check',
            self::Medium => 'tabler-shield-exclamation',
            self::Low => 'tabler-shield-off',
        };
    }
}
