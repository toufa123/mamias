<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Review status for literature references in the catalogue.
 *
 * Tracks whether a submitted reference has been reviewed and approved
 * by a moderator before being linked to species records.
 */
enum LiteratureStatus: string implements HasColor, HasIcon, HasLabel
{
    /** Pending review by a moderator. */
    case PENDING = 'pending';

    /** Approved and linked to catalogue records. */
    case APPROVED = 'approved';

    /** Rejected — not suitable for the catalogue. */
    case REJECTED = 'rejected';

    /**
     * Human-readable label for the literature review status.
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
