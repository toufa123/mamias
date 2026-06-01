<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Habitat: string implements HasColor, HasLabel
{
    case SEAGRASS_MEADOWS = 'seagrass_meadows';
    case ROCKS = 'rocks';
    case SAND = 'sand';
    case UNKNOWN = 'unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'Seagrass meadows',
            self::ROCKS => 'Rocks',
            self::SAND => 'Sand',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SEAGRASS_MEADOWS => 'success',
            self::ROCKS => 'gray',
            self::SAND => 'warning',
            self::UNKNOWN => 'info',
        };
    }
}
