<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EstablishmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Casual = 'Casual';
    case Established = 'Established';
    case Unknown = 'Unknown';
    case Invasive = 'Invasive';
    case DataDeficient = 'Data Deficient';
    case Excluded = 'Excluded';
    case Questionable = 'Questionable';
    case Vagrant = 'Vagrant';
    case RangeExpansion = 'Range expansion';

    public function getLabel(): ?string
    {
        return $this->value;
    }

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
            self::RangeExpansion => 'info',
        };
    }

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
            self::RangeExpansion => 'tabler-arrows-maximize',
        };
    }
}
