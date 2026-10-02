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

    /** Species naturally expanding its range into new areas. */
    case RangeExpansion = 'Range Expansion';

    /** Held out of the validated baseline: debatable, likely alien, or awaiting identification. */
    case DataDeficient = 'Data Deficient';

    /**
     * How the baselines write a questionable record, often in their
     * establishment column. It is a NIS status (Galanidi et al. 2023: a
     * record with insufficient information or uncertain identification),
     * never an establishment status, so importers move it here.
     */
    public const QUESTIONABLE_CODES = ['que', 'ques', 'qr', 'questionable', 'questioable'];

    public static function isQuestionableCode(?string $raw): bool
    {
        $cleaned = preg_replace(['/\s*\([^)]*\)\s*/', '/\?+$/'], '', mb_strtolower(trim((string) $raw)));

        return in_array(trim((string) $cleaned), self::QUESTIONABLE_CODES, true);
    }

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
            self::RangeExpansion => 'Species naturally expanding its range into new areas',
            self::DataDeficient => 'Species held out of the validated baseline pending expert consensus or identification',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NIS => 'primary',
            self::Cryptogenic => 'gray',
            self::Questionable => 'gray',
            self::RangeExpansion => 'native',
            self::DataDeficient => 'gray',
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
            self::RangeExpansion => 'tabler-arrows-maximize',
            self::DataDeficient => 'tabler-help-hexagon',
        };
    }
}
