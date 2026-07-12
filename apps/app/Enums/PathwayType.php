<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Type of introduction pathway for a species record.
 *
 * Distinguishes between primary introduction (direct from native range)
 * and secondary introduction (from an already-established introduced population).
 */
enum PathwayType: string implements HasLabel
{
    /** Primary introduction — direct from the species' native range. */
    case Primary = 'primary';

    /** Secondary introduction — from an already-established non-native population. */
    case Secondary = 'secondary';

    /**
     * Human-readable label for the pathway type.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Secondary => 'Secondary',
        };
    }
}
