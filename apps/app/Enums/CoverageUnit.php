<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Unit the recorded coverage figure is expressed in.
 *
 * Sessile and encrusting species are recorded as the surface area they cover,
 * mobile ones as a head count, so a single coverage figure is only meaningful
 * alongside the unit it was taken in.
 */
enum CoverageUnit: string implements HasLabel
{
    /** Surface area covered, in square metres. */
    case SQUARE_METRES = 'm2';

    /** Head count of individuals observed. */
    case INDIVIDUALS = 'individuals';

    /**
     * Human-readable label for the coverage unit.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::SQUARE_METRES => 'Coverage (m²)',
            self::INDIVIDUALS => 'Number of individuals',
        };
    }

    /**
     * Compact suffix for displaying the unit next to a value.
     */
    public function getSuffix(): string
    {
        return match ($this) {
            self::SQUARE_METRES => 'm²',
            self::INDIVIDUALS => 'ind.',
        };
    }
}
