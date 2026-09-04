<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Establishment status categories for a species in a given area.
 *
 * Describes whether a non-indigenous species is casual, established, invasive,
 * or otherwise categorised in the recipient region.
 */
enum EstablishmentStatus: string implements HasColor, HasIcon, HasLabel
{
    /** Casual — present but not self-sustaining. */
    case Casual = 'Casual';

    /** Established — self-sustaining population. */
    case Established = 'Established';

    /** Unknown — establishment status not determined. */
    case Unknown = 'Unknown';

    /** Invasive — established with negative ecological or economic impacts. */
    case Invasive = 'Invasive';

    /** Data Deficient — insufficient data to assess establishment. */
    case DataDeficient = 'Data Deficient';

    /** Excluded — confirmed absent or excluded from the area. */
    case Excluded = 'Excluded';

    /** Questionable — record is doubtful or unverified. */
    case Questionable = 'Questionable';

    /** Vagrant — occasional visitor, not self-sustaining. */
    case Vagrant = 'Vagrant';

    /** Range expansion — native range expanding into new areas. */

    /**
     * Human-readable label for the establishment status.
     */
    public function getLabel(): ?string
    {
        return $this->value;
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Casual => 'warning',
            self::Established => 'success',
            self::Unknown => 'gray',
            self::Invasive => 'danger',
            self::DataDeficient => 'gray',
            self::Excluded => 'danger',
            self::Questionable => 'warning',
            self::Vagrant => 'info',

        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Casual => 'tabler-clock',
            self::Established => 'tabler-circle-check',
            self::Unknown => 'tabler-help',
            self::Invasive => 'tabler-alert-triangle',
            self::DataDeficient => 'tabler-database-off',
            self::Excluded => 'tabler-x',
            self::Questionable => 'tabler-help-circle',
            self::Vagrant => 'tabler-plane-arrival',

        };
    }
}
