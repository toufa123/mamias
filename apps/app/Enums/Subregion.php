<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Mediterranean Sea subregions for species occurrence tracking.
 *
 * Divides the Mediterranean basin into four standard subregions
 * for biogeographic analysis and reporting.
 */
enum Subregion: string implements HasColor, HasIcon, HasLabel
{
    /** Western Mediterranean Basin. */
    case WMED = 'WMED';

    /** Central Mediterranean Basin. */
    case CMED = 'CMED';

    /** Adriatic Sea. */
    case ADRIA = 'ADRIA';

    /** Eastern Mediterranean Basin including the Levantine Sea. */
    case EMED = 'EMED';

    /**
     * Human-readable label for the Mediterranean subregion.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::WMED => 'Western Mediterranean',
            self::CMED => 'Central Mediterranean',
            self::ADRIA => 'Adriatic Sea',
            self::EMED => 'Eastern Mediterranean',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return 'primary';
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return 'tabler-map-pin';
    }

    /**
     * Alias for getLabel, required by some Filament components.
     */
    public function label(): string
    {
        return $this->getLabel();
    }
}
