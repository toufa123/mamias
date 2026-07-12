<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Marine and adjacent environment types.
 *
 * Used to classify species by their primary habitat environment,
 * with methods to parse WoRMS environment flags and resolve
 * environment values from labels.
 */
enum Environment: string implements HasColor, HasIcon, HasLabel
{
    /** Marine environment (saltwater). */
    case marine = 'marine';

    /** Freshwater environment (inland waters). */
    case freshwater = 'freshwater';

    /** Brackish environment (mix of fresh and saltwater, e.g., estuaries). */
    case brackish = 'brackish';

    /** Terrestrial environment (land). */
    case terrestrial = 'terrestrial';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::marine => 'Marine',
            self::freshwater => 'Freshwater',
            self::brackish => 'Brackish',
            self::terrestrial => 'Terrestrial',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::marine => '#4166F5',
            self::freshwater => '#45ADA8',
            self::brackish => '#8E7F73',
            self::terrestrial => '#A0A0A0',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::marine => 'tabler-waves',
            self::freshwater => 'tabler-droplet',
            self::brackish => 'tabler-ripple',
            self::terrestrial => 'tabler-mountain',
        };
    }

    /**
     * Alias for getLabel, required by some Filament components.
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Parse an environment value from a label string or raw value.
     *
     * Accepts the enum instance itself (returned as-is), a raw value matching
     * the backed string, or a human-readable label (e.g., "Marine" → marine).
     */
    public static function fromLabelOrValue(null|string|self $state): ?self
    {
        if ($state instanceof self) {
            return $state;
        }

        $normalized = strtolower(trim((string) $state));

        if ($normalized === '') {
            return null;
        }

        return self::tryFrom($normalized)
            ?? collect(self::cases())->first(
                fn (self $case) => strtolower((string) $case->getLabel()) === $normalized
            );
    }

    /**
     * Extract environment types from WoRMS API response flags.
     *
     * Maps WoRMS boolean flags (isMarine, isBrackish, isFreshwater, isTerrestrial)
     * to the corresponding Environment enum values.
     *
     * @param  array  $item  WoRMS taxon record array.
     * @return string[] List of environment values.
     */
    public static function fromWormsData(array $item): array
    {
        $environments = [];

        if (! empty($item['isMarine'])) {
            $environments[] = self::marine->value;
        }

        if (! empty($item['isBrackish'])) {
            $environments[] = self::brackish->value;
        }

        if (! empty($item['isFreshwater'])) {
            $environments[] = self::freshwater->value;
        }

        if (! empty($item['isTerrestrial'])) {
            $environments[] = self::terrestrial->value;
        }

        return $environments;
    }
}
