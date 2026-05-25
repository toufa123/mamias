<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CbdPathwayCategory: string implements HasLabel
{
    case ReleaseIntoNature = '1';
    case EscapeFromConfinement = '2';
    case TransportStowaway = '3';
    case TransportContaminant = '4';
    case Corridor = '5';
    case Unaided = '6';

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
