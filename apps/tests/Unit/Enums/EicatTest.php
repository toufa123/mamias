<?php

declare(strict_types=1);

use App\Enums\AssessmentScale;
use App\Enums\DataQuality;
use App\Enums\EicatCategory;
use App\Enums\EicatMechanism;
use Tests\TestCase;

uses(TestCase::class);

describe('EicatCategory', function () {
    it('returns all labels', function () {
        expect(EicatCategory::Massive->getLabel())->toBe('Massive (MV)')
            ->and(EicatCategory::Major->getLabel())->toBe('Major (MR)')
            ->and(EicatCategory::Moderate->getLabel())->toBe('Moderate (MO)')
            ->and(EicatCategory::Minor->getLabel())->toBe('Minor (MN)')
            ->and(EicatCategory::MinimalConcern->getLabel())->toBe('Minimal Concern (MC)')
            ->and(EicatCategory::DataDeficient->getLabel())->toBe('Data Deficient (DD)');
    });

    it('returns all descriptions', function () {
        expect(EicatCategory::Massive->getDescription())->toBe('Irreversible ecosystem-level changes')
            ->and(EicatCategory::DataDeficient->getDescription())->toBe('Insufficient data to classify impact');
    });

    it('returns all colors', function () {
        expect(EicatCategory::Massive->getColor())->toBe('danger')
            ->and(EicatCategory::MinimalConcern->getColor())->toBe('success');
    });

    it('returns all icons', function () {
        expect(EicatCategory::Massive->getIcon())->toBe('tabler-alert-triangle')
            ->and(EicatCategory::MinimalConcern->getIcon())->toBe('tabler-circle-check');
    });
});

describe('EicatMechanism', function () {
    it('returns all labels', function () {
        expect(EicatMechanism::Competition->getLabel())->toBe('Competition')
            ->and(EicatMechanism::ChemicalImpact->getLabel())->toBe('Chemical impact on ecosystem')
            ->and(EicatMechanism::Other->getLabel())->toBe('Other');
    });
});

describe('AssessmentScale', function () {
    it('returns all labels', function () {
        expect(AssessmentScale::Global->getLabel())->toBe('Global')
            ->and(AssessmentScale::Regional->getLabel())->toBe('Regional')
            ->and(AssessmentScale::National->getLabel())->toBe('National');
    });
});

describe('DataQuality', function () {
    it('returns all labels', function () {
        expect(DataQuality::High->getLabel())->toBe('High')
            ->and(DataQuality::NA->getLabel())->toBe('N/A');
    });

    it('returns all colors', function () {
        expect(DataQuality::High->getColor())->toBe('success')
            ->and(DataQuality::Low->getColor())->toBe('danger');
    });

    it('returns all icons', function () {
        expect(DataQuality::High->getIcon())->toBe('tabler-shield-check')
            ->and(DataQuality::Low->getIcon())->toBe('tabler-shield-off');
    });
});
