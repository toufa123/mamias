<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Catalogue validation status of a taxon record against WoRMS data.
 *
 * Tracks whether a species entry has been verified against the World Register
 * of Marine Species and whether the name is currently accepted.
 */
enum Catalogue_Status: string implements HasColor, HasIcon, HasLabel
{
    /** The entry has been checked and the name is accepted in WoRMS. */
    case checked_accepted = 'checked & accepted';

    /** The entry has been checked but the name is not accepted in WoRMS. */
    case checked_not_accepted = 'checked & not accepted';

    /** The entry has not yet been checked against WoRMS data. */
    case not_checked = 'not checked yet';

    /** No data was available from WoRMS for this entry. */
    case no_data_from_worms = 'no data from WORMS';

    /**
     * Human-readable label for the catalogue status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::checked_accepted => 'Checked & accepted',
            self::checked_not_accepted => 'Checked & not accepted',
            self::not_checked => 'Not checked Yet',
            self::no_data_from_worms => 'No data from WORMS',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::checked_accepted => 'success',
            self::checked_not_accepted => 'danger',
            self::not_checked => 'danger',
            self::no_data_from_worms => 'danger',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::checked_accepted => 'tabler-circle-check',
            self::checked_not_accepted => 'tabler-circle-x',
            self::not_checked => 'tabler-clock',
            self::no_data_from_worms => 'tabler-database-x',
        };
    }

    /**
     * Alias for getLabel, required by some Filament components.
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Derive the catalogue status from a WoRMS taxon status.
     *
     * Accepted and alternative-representation statuses map to "checked & accepted";
     * all others map to "checked & not accepted". Null or empty input returns
     * "no data from WORMS".
     */
    public static function fromWormsData(Worms_Status|string|null $status): self
    {
        if ($status === null || $status === '') {
            return self::no_data_from_worms;
        }

        $statusValue = $status instanceof Worms_Status ? $status->value : $status;

        return in_array($statusValue, [
            Worms_Status::accepted->value,
            Worms_Status::alternative_representation->value,
        ])
            ? self::checked_accepted
            : self::checked_not_accepted;
    }
}
