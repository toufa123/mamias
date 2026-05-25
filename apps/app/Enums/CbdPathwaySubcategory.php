<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CbdPathwaySubcategory: string implements HasLabel
{
    // Category 1: Release into Nature
    case Release_1_1 = '1.1';
    case Release_1_2 = '1.2';
    case Release_1_3 = '1.3';

    // Category 2: Escape from Confinement
    case Escape_2_1 = '2.1';
    case Escape_2_2 = '2.2';
    case Escape_2_3 = '2.3';
    case Escape_2_4 = '2.4';

    // Category 3: Transport - Stowaway
    case TransportStowaway_3_1 = '3.1';
    case TransportStowaway_3_2 = '3.2';
    case TransportStowaway_3_3 = '3.3';
    case TransportStowaway_3_4 = '3.4';
    case TransportStowaway_3_5 = '3.5';

    // Category 4: Transport - Contaminant
    case TransportContaminant_4_1 = '4.1';
    case TransportContaminant_4_2 = '4.2';
    case TransportContaminant_4_3 = '4.3';

    // Category 5: Corridor
    case Corridor_5_1 = '5.1';
    case Corridor_5_2 = '5.2';

    // Category 6: Unaided (Natural Dispersal)
    case Unaided_6_1 = '6.1';
    case Unaided_6_2 = '6.2';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Release_1_1 => '1.1: Native organisms in new environment (hunting, fishing, angling)',
            self::Release_1_2 => '1.2: Native organisms released for specific purpose (biocontrol, pet release)',
            self::Release_1_3 => '1.3: Non-native organisms released in nature (pet/aquarium/terrarium species, garden plants)',
            self::Escape_2_1 => '2.1: Escape from agriculture/mariculture/aquaculture',
            self::Escape_2_2 => '2.2: Escape from pet/aquarium/terrarium/garden/zoological collection',
            self::Escape_2_3 => '2.3: Escape from research facilities/contained use',
            self::Escape_2_4 => '2.4: Escape from transport-related confined spaces (ballast water, hull fouling)',
            self::TransportStowaway_3_1 => '3.1: Shipping (ballast water, hull fouling, sediments)',
            self::TransportStowaway_3_2 => '3.2: Marine debris (floating plastic, debris)',
            self::TransportStowaway_3_3 => '3.3: Vehicles (cars, trucks, trains)',
            self::TransportStowaway_3_4 => '3.4: Packing material, containers, products',
            self::TransportStowaway_3_5 => '3.5: Tourism and leisure (fishing gear, diving equipment, recreational boats)',
            self::TransportContaminant_4_1 => '4.1: Seed, fodder, plant material contamination',
            self::TransportContaminant_4_2 => '4.2: Parasitic organisms on imported hosts',
            self::TransportContaminant_4_3 => '4.3: Contamination in imported products/materials',
            self::Corridor_5_1 => '5.1: Interoceanic canals (e.g., Suez Canal, Gibraltar)',
            self::Corridor_5_2 => '5.2: Interbasin transfer (river diversions, water transfer schemes)',
            self::Unaided_6_1 => '6.1: Natural dispersal from previously introduced populations',
            self::Unaided_6_2 => '6.2: Range expansion due to climate change',
        };
    }
}
