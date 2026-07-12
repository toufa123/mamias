<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * ACFOR (Abundant/Common/Frequent/Occasional/Rare) abundance scale.
 *
 * Standard semi-quantitative scale for recording species abundance,
 * with density descriptions for both animal (ind./m²) and plant (% cover) observations.
 */
enum AcforScale: string implements HasColor, HasLabel
{
    /** Rare — <0.1 ind./m² or <5% cover. */
    case RARE = 'rare';

    /** Occasional — 0.1–1 ind./m² or 5–25% cover. */
    case OCCASIONAL = 'occasional';

    /** Frequent — 1–10 ind./m² or 25–50% cover. */
    case FREQUENT = 'frequent';

    /** Common — 10–100 ind./m² or 50–75% cover. */
    case COMMON = 'common';

    /** Abundant — >100 ind./m² or >75% cover. */
    case ABUNDANT = 'abundant';

    /**
     * Human-readable label for the scale value.
     */
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

    /**
     * Filament color for UI display.
     */
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

    /**
     * Density description for animal (invertebrate) observations in individuals per m².
     */
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

    /**
     * Density description for plant (algae/seagrass) observations as percentage cover.
     */
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
