<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Habitat types for species occurrence records.
 *
 * Describes the predominant benthic substrate or habitat where
 * a species was observed.
 */
enum Habitat: string implements HasColor, HasIcon, HasLabel
{
    /** Seagrass meadow habitat. */
    case SEAGRASS_MEADOWS = 'seagrass_meadows';

    /** Rocky substrate habitat. */
    case ROCKS = 'rocks';

    /** Sandy substrate habitat. */
    case SAND = 'sand';

    /** Habitat type is unknown or not recorded. */
    case UNKNOWN = 'unknown';

    /**
     * Human-readable label for the habitat type.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'Seagrass meadows',
            self::ROCKS => 'Rocks',
            self::SAND => 'Sand',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'gray',
            self::ROCKS => 'gray',
            self::SAND => 'gray',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * The icon names the habitat; the colour stays neutral, because a category is not a status. See DESIGN-SYSTEM.md.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'tabler-plant-2',
            self::ROCKS => 'tabler-mountain',
            self::SAND => 'tabler-beach',
            self::UNKNOWN => 'tabler-help',
        };
    }
}
