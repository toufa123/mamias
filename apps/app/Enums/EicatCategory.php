<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * EICAT (Environmental Impact Classification for Alien Taxa) impact categories.
 *
 * Standardised classification of environmental impacts caused by alien species,
 * ranging from Minimal Concern to Massive (irreversible ecosystem-level changes).
 */
enum EicatCategory: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    /** Irreversible ecosystem-level changes. */
    case Massive = 'MV';

    /** Significant changes to ecosystem structure or function. */
    case Major = 'MR';

    /** Moderate changes to native species or communities. */
    case Moderate = 'MO';

    /** Minor changes to native species or communities. */
    case Minor = 'MN';

    /** No evidence of negative environmental impacts. */
    case MinimalConcern = 'MC';

    /** Insufficient data to classify impact. */
    case DataDeficient = 'DD';

    /**
     * Human-readable label for the EICAT category.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Massive => 'Massive (MV)',
            self::Major => 'Major (MR)',
            self::Moderate => 'Moderate (MO)',
            self::Minor => 'Minor (MN)',
            self::MinimalConcern => 'Minimal Concern (MC)',
            self::DataDeficient => 'Data Deficient (DD)',
        };
    }

    /**
     * Description of the impact level for this EICAT category.
     */
    public function getDescription(): ?string
    {
        return match ($this) {
            self::Massive => 'Irreversible ecosystem-level changes',
            self::Major => 'Significant changes to ecosystem structure or function',
            self::Moderate => 'Moderate changes to native species or communities',
            self::Minor => 'Minor changes to native species or communities',
            self::MinimalConcern => 'No evidence of negative environmental impacts',
            self::DataDeficient => 'Insufficient data to classify impact',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Massive => 'danger',
            self::Major => 'warning',
            self::Moderate => 'orange',
            self::Minor => 'yellow',
            self::MinimalConcern => 'success',
            self::DataDeficient => 'gray',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Massive => 'tabler-alert-triangle',
            self::Major => 'tabler-alert-circle',
            self::Moderate => 'tabler-alert-square',
            self::Minor => 'tabler-info-circle',
            self::MinimalConcern => 'tabler-circle-check',
            self::DataDeficient => 'tabler-database-off',
        };
    }
}
