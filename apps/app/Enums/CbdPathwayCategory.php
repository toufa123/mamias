<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * CBD (Convention on Biological Diversity) pathway categories for species introduction.
 *
 * Categories 1–6 as defined by the CBD classification framework for
 * pathways of introduction of invasive alien species.
 */
enum CbdPathwayCategory: string implements HasLabel
{
    /** Release into nature (e.g., biocontrol, stocking). */
    case ReleaseIntoNature = '1';

    /** Escape from confinement (e.g., aquaculture, pet trade). */
    case EscapeFromConfinement = '2';

    /** Transport as stowaway (e.g., ballast water, hull fouling). */
    case TransportStowaway = '3';

    /** Transport as contaminant (e.g., seed contamination, parasites). */
    case TransportContaminant = '4';

    /** Corridor (e.g., canals, interbasin connections). */
    case Corridor = '5';

    /** Unaided — natural dispersal from previously introduced populations. */
    case Unaided = '6';

    /**
     * Human-readable label for the pathway category.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::ReleaseIntoNature => '1. Release into Nature',
            self::EscapeFromConfinement => '2. Escape from Confinement',
            self::TransportStowaway => '3. Transport - Stowaway',
            self::TransportContaminant => '4. Transport - Contaminant',
            self::Corridor => '5. Corridor',
            self::Unaided => '6. Unaided (Natural Dispersal)',
        };
    }
}
