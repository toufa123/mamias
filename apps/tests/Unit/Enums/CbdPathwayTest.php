<?php

declare(strict_types=1);

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\PathwayType;
use Tests\TestCase;

uses(TestCase::class);

describe('CbdPathwayCategory', function () {
    it('returns all labels', function () {
        expect(CbdPathwayCategory::ReleaseIntoNature->getLabel())->toBe('1. Release into Nature')
            ->and(CbdPathwayCategory::EscapeFromConfinement->getLabel())->toBe('2. Escape from Confinement')
            ->and(CbdPathwayCategory::TransportStowaway->getLabel())->toBe('3. Transport - Stowaway')
            ->and(CbdPathwayCategory::TransportContaminant->getLabel())->toBe('4. Transport - Contaminant')
            ->and(CbdPathwayCategory::Corridor->getLabel())->toBe('5. Corridor')
            ->and(CbdPathwayCategory::Unaided->getLabel())->toBe('6. Unaided (Natural Dispersal)');
    });
});

describe('CbdPathwaySubcategory', function () {
    it('returns labels for release subcategories', function () {
        expect(CbdPathwaySubcategory::Release_1_1->getLabel())->toStartWith('1.1')
            ->and(CbdPathwaySubcategory::Release_1_3->getLabel())->toContain('Non-native');
    });

    it('returns labels for transport subcategories', function () {
        expect(CbdPathwaySubcategory::TransportStowaway_3_1->getLabel())->toContain('Shipping')
            ->and(CbdPathwaySubcategory::TransportContaminant_4_3->getLabel())->toContain('Contamination');
    });

    it('returns labels for unaided subcategories', function () {
        expect(CbdPathwaySubcategory::Unaided_6_1->getLabel())->toContain('Natural dispersal');
    });
});

describe('PathwayType', function () {
    it('returns all labels', function () {
        expect(PathwayType::Primary->getLabel())->toBe('Primary')
            ->and(PathwayType::Secondary->getLabel())->toBe('Secondary');
    });
});
