<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Catalogue_Status: string implements HasColor, HasIcon, HasLabel
{
    /**
     * The status of the catalogue entry, based on the WoRMS data.
     * - checked_accepted: The entry has been checked and is accepted in WoRMS.
     * - checked_not_accepted: The entry has been checked but is not accepted in WoRMS.
     * - not_checked: The entry has not been checked against WoRMS data and should be for imported data.
     * - no_data_from_worms: There was no data available from WoRMS for this entry.
     */
    case checked_accepted = 'checked & accepted';
    case checked_not_accepted = 'checked & not accepted';
    case not_checked = 'not checked yet';
    case no_data_from_worms = 'no data from WORMS';

    public function getLabel(): string
    {
        return match ($this) {
            self::checked_accepted => 'Checked & accepted',
            self::checked_not_accepted => 'Checked & not accepted',
            self::not_checked => 'Not checked Yet',
            self::no_data_from_worms => 'No data from WORMS',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::checked_accepted => 'success',
            self::checked_not_accepted => 'danger',
            self::not_checked => 'danger',
            self::no_data_from_worms => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::checked_accepted => 'tabler-circle-check',
            self::checked_not_accepted => 'tabler-circle-x',
            self::not_checked => 'tabler-clock',
            self::no_data_from_worms => 'tabler-database-x',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

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
