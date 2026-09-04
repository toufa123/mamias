<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * How the recorded coverage figure was obtained.
 *
 * A field estimate and an instrument measurement carry very different
 * confidence, so the figure is stored with the way it was arrived at.
 */
enum CoverageMethod: string implements HasColor, HasIcon, HasLabel
{
    /** Judged visually in the field, without instruments. */
    case ESTIMATED = 'estimated';

    /** Quantified with a transect, quadrat, or other instrument. */
    case MEASURED = 'measured';

    /**
     * Human-readable label for the coverage method.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::ESTIMATED => 'Estimated',
            self::MEASURED => 'Measured',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::ESTIMATED => 'tabler-eye',
            self::MEASURED => 'tabler-ruler-measure',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ESTIMATED => 'warning',
            self::MEASURED => 'success',
        };
    }
}
