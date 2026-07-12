<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Abundance categories for non-indigenous species observations.
 *
 * Ranks species abundance from rare to dominant, with corresponding
 * Filament color indicators for UI display.
 */
enum AbundanceCategory: string implements HasColor, HasLabel
{
    /** Species is rarely observed in the area. */
    case RARE = 'rare';

    /** Species is occasionally observed in the area. */
    case OCCASIONAL = 'occasional';

    /** Species is commonly observed in the area. */
    case COMMON = 'common';

    /** Species is abundant in the area. */
    case ABUNDANT = 'abundant';

    /** Species is dominant in the area. */
    case DOMINANT = 'dominant';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RARE => 'Rare',
            self::OCCASIONAL => 'Occasional',
            self::COMMON => 'Common',
            self::ABUNDANT => 'Abundant',
            self::DOMINANT => 'Dominant',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RARE => 'gray',
            self::OCCASIONAL => 'info',
            self::COMMON => 'success',
            self::ABUNDANT => 'warning',
            self::DOMINANT => 'danger',
        };
    }
}
