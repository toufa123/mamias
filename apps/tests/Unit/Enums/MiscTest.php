<?php

declare(strict_types=1);

use App\Enums\AbundanceCategory;
use App\Enums\AcforScale;
use App\Enums\EstablishmentStatus;
use App\Enums\Habitat;
use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Enums\NisStatus;
use App\Enums\OccurrenceStatus;
use App\Enums\Subregion;
use App\Enums\Worms_Status;
use Tests\TestCase;

uses(TestCase::class);

describe('AbundanceCategory', function () {
    it('returns all labels', function () {
        expect(AbundanceCategory::RARE->getLabel())->toBe('Rare')
            ->and(AbundanceCategory::DOMINANT->getLabel())->toBe('Dominant');
    });

    it('returns all colors', function () {
        expect(AbundanceCategory::RARE->getColor())->toBe('gray')
            ->and(AbundanceCategory::DOMINANT->getColor())->toBe('danger');
    });
});

describe('AcforScale', function () {
    it('returns all labels', function () {
        expect(AcforScale::RARE->getLabel())->toBe('Rare (R)')
            ->and(AcforScale::ABUNDANT->getLabel())->toBe('Abundant (A)');
    });

    it('returns animal density descriptions', function () {
        expect(AcforScale::RARE->getAnimalDescription())->toBe('<0.1 ind./m²')
            ->and(AcforScale::ABUNDANT->getAnimalDescription())->toBe('>100 ind./m²');
    });

    it('returns plant cover descriptions', function () {
        expect(AcforScale::RARE->getPlantDescription())->toBe('<5% cover')
            ->and(AcforScale::ABUNDANT->getPlantDescription())->toBe('>75% cover');
    });
});

describe('EstablishmentStatus', function () {
    it('returns all labels', function () {
        expect(EstablishmentStatus::Casual->getLabel())->toBe('Casual')
            ->and(EstablishmentStatus::Invasive->getLabel())->toBe('Invasive')
            ->and(EstablishmentStatus::DataDeficient->getLabel())->toBe('Data Deficient');
    });
});

describe('Habitat', function () {
    it('returns all labels', function () {
        expect(Habitat::SEAGRASS_MEADOWS->getLabel())->toBe('Seagrass meadows')
            ->and(Habitat::UNKNOWN->getLabel())->toBe('Unknown');
    });
});

describe('LiteratureStatus', function () {
    it('returns all labels', function () {
        expect(LiteratureStatus::PENDING->getLabel())->toBe('Pending Review')
            ->and(LiteratureStatus::APPROVED->getLabel())->toBe('Approved')
            ->and(LiteratureStatus::REJECTED->getLabel())->toBe('Rejected');
    });

    it('returns all icons', function () {
        expect(LiteratureStatus::PENDING->getIcon())->toBe('tabler-clock')
            ->and(LiteratureStatus::APPROVED->getIcon())->toBe('tabler-check');
    });
});

describe('LiteratureType', function () {
    it('returns all labels', function () {
        expect(LiteratureType::ARTICLE->getLabel())->toBe('Article')
            ->and(LiteratureType::CONFERENCE_PROCEEDINGS->getLabel())->toBe('Conference Proceedings');
    });
});

describe('NisStatus', function () {
    it('returns all labels', function () {
        expect(NisStatus::NIS->getLabel())->toBe('NIS')
            ->and(NisStatus::Cryptogenic->getLabel())->toBe('Cryptogenic')
            ->and(NisStatus::Questionable->getLabel())->toBe('Questionable')
            ->and(NisStatus::RangeExpansion->getLabel())->toBe('Range Expansion');
    });

    it('returns descriptions', function () {
        expect(NisStatus::NIS->getDescription())->toContain('introduced')
            ->and(NisStatus::RangeExpansion->getDescription())->toContain('expanding');
    });
});

describe('OccurrenceStatus', function () {
    it('returns all labels', function () {
        expect(OccurrenceStatus::PENDING->getLabel())->toBe('Pending Review')
            ->and(OccurrenceStatus::APPROVED->getLabel())->toBe('Approved');
    });
});

describe('Subregion', function () {
    it('returns all labels', function () {
        expect(Subregion::WMED->getLabel())->toBe('Western Mediterranean')
            ->and(Subregion::EMED->getLabel())->toBe('Eastern Mediterranean');
    });
});

describe('Worms_Status', function () {
    it('returns accepted label', function () {
        expect(Worms_Status::accepted->getLabel())->toBe('Accepted');
    });

    it('returns all colors for accepted vs unaccepted', function () {
        expect(Worms_Status::accepted->getColor())->toBe('success')
            ->and(Worms_Status::unaccepted->getColor())->toBe('danger');
    });

    it('labels at least 28 status values', function () {
        $cases = Worms_Status::cases();
        expect(count($cases))->toBeGreaterThanOrEqual(28);
        foreach ($cases as $case) {
            expect($case->getLabel())->toBeString();
        }
    });
});
