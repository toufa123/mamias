<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AcforScale: string implements HasColor, HasLabel
{
    case RARE = 'rare';
    case OCCASIONAL = 'occasional';
    case FREQUENT = 'frequent';
    case COMMON = 'common';
    case ABUNDANT = 'abundant';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RARE => 'Rare (R)',
            self::OCCASIONAL => 'Occasional (O)',
            self::FREQUENT => 'Frequent (F)',
            self::COMMON => 'Common (C)',
            self::ABUNDANT => 'Abundant (A)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RARE => 'gray',
            self::OCCASIONAL => 'info',
            self::FREQUENT => 'success',
            self::COMMON => 'warning',
            self::ABUNDANT => 'danger',
        };
    }

    public function getAnimalDescription(): string
    {
        return match ($this) {
            self::RARE => '<0.1 ind./m²',
            self::OCCASIONAL => '0.1–1 ind./m²',
            self::FREQUENT => '1–10 ind./m²',
            self::COMMON => '10–100 ind./m²',
            self::ABUNDANT => '>100 ind./m²',
        };
    }

    public function getPlantDescription(): string
    {
        return match ($this) {
            self::RARE => '<5% cover',
            self::OCCASIONAL => '5–25% cover',
            self::FREQUENT => '25–50% cover',
            self::COMMON => '50–75% cover',
            self::ABUNDANT => '>75% cover',
        };
    }
}
