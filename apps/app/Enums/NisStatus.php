<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Non-Indigenous Species (NIS) status classification.
 *
 * Categorises a species record as definitively non-indigenous,
 * cryptogenic (unknown origin), or questionable (unverified).
 */
enum NisStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    /** Species introduced outside its native range. */
    case NIS = 'NIS';

    /** Species with unknown native range or pathway of introduction. */
    case Cryptogenic = 'Cryptogenic';

    /** Species with unresolved taxonomic status or not verified by experts. */
    case Questionable = 'Questionable';

    /**
     * Human-readable label for the NIS status.
     */
    public function getLabel(): ?string
    {
        return $this->value;
    }

    /**
     * Detailed description of what this NIS status means.
     */
    public function getDescription(): ?string
    {
        return match ($this) {
            self::NIS => 'Species introduced outside its native range',
            self::Cryptogenic => 'Species with unknown native range or pathway of introduction',
            self::Questionable => 'Species with unresolved taxonomic status or not verified by experts',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NIS => 'success',
            self::Cryptogenic => 'warning',
            self::Questionable => 'danger',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::NIS => 'tabler-world',
            self::Cryptogenic => 'tabler-help',
            self::Questionable => 'tabler-alert-circle',
        };
    }
}
