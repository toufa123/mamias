<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Abundance categories for non-indigenous species observations.
 *
 * Ranks species abundance from rare to dominant, with corresponding
 * Filament color indicators for UI display.
 */
enum AbundanceCategory: string implements HasColor, HasIcon, HasLabel
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
            self::RARE => 'primary',
            self::OCCASIONAL => 'primary',
            self::COMMON => 'primary',
            self::ABUNDANT => 'primary',
            self::DOMINANT => 'primary',
        };
    }

    /**
     * Signal bars carry the step (1–5); the colour is one teal, because a quantity scale is not a status. See DESIGN-SYSTEM.md.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::RARE => 'tabler-antenna-bars-1',
            self::OCCASIONAL => 'tabler-antenna-bars-2',
            self::COMMON => 'tabler-antenna-bars-3',
            self::ABUNDANT => 'tabler-antenna-bars-4',
            self::DOMINANT => 'tabler-antenna-bars-5',
        };
    }
}
