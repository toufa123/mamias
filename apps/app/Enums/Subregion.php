<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * The four EcAp subregions of the Mediterranean (Barcelona Convention,
 * UNEP/MAP Ecosystem Approach), used for biogeographic analysis and reporting.
 *
 * The stored codes predate the EcAp names: CMED is the Ionian Sea and
 * Central Mediterranean, EMED the Aegean-Levantine Sea.
 */
enum Subregion: string implements HasColor, HasIcon, HasLabel
{
    /** Western Mediterranean. */
    case WMED = 'WMED';

    /** Ionian Sea and Central Mediterranean. */
    case CMED = 'CMED';

    /** Adriatic Sea. */
    case ADRIA = 'ADRIA';

    /** Aegean-Levantine Sea. */
    case EMED = 'EMED';

    /**
     * Human-readable label for the Mediterranean subregion.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::WMED => 'Western Mediterranean',
            self::CMED => 'Ionian Sea and Central Mediterranean',
            self::ADRIA => 'Adriatic Sea',
            self::EMED => 'Aegean-Levantine Sea',
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
