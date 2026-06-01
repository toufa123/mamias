<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AbundanceCategory: string implements HasColor, HasLabel
{
    case RARE = 'rare';
    case OCCASIONAL = 'occasional';
    case COMMON = 'common';
    case ABUNDANT = 'abundant';
    case DOMINANT = 'dominant';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RARE => 'Rare',
            self::OCCASIONAL => 'Occasional',
            self::COMMON => 'Common',
            self::ABUNDANT => 'Abundant',
            self::DOMINANT => 'Dominant',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RARE => 'gray',
            self::OCCASIONAL => 'info',
            self::COMMON => 'success',
            self::ABUNDANT => 'warning',
            self::DOMINANT => 'danger',
        };
    }
}
