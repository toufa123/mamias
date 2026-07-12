<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Review status for species occurrence records.
 *
 * Tracks whether a submitted occurrence record has been reviewed
 * and approved by a moderator.
 */
enum OccurrenceStatus: string implements HasColor, HasIcon, HasLabel
{
    /** Pending review by a moderator. */
    case PENDING = 'pending';

    /** Approved and visible in the catalogue. */
    case APPROVED = 'approved';

    /** Rejected — not valid or insufficient data. */
    case REJECTED = 'rejected';

    /**
     * Human-readable label for the occurrence review status.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'tabler-clock',
            self::APPROVED => 'tabler-check',
            self::REJECTED => 'tabler-x',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
