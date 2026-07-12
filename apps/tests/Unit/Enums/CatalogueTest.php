<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use Tests\TestCase;

uses(TestCase::class);

it('returns all catalogue status labels', function () {
    expect(Catalogue_Status::checked_accepted->getLabel())->toBe('Checked & accepted')
        ->and(Catalogue_Status::checked_not_accepted->getLabel())->toBe('Checked & not accepted')
        ->and(Catalogue_Status::not_checked->getLabel())->toBe('Not checked Yet')
        ->and(Catalogue_Status::no_data_from_worms->getLabel())->toBe('No data from WORMS');
});

it('returns all catalogue status colors', function () {
    expect(Catalogue_Status::checked_accepted->getColor())->toBe('success')
        ->and(Catalogue_Status::checked_not_accepted->getColor())->toBe('danger')
        ->and(Catalogue_Status::not_checked->getColor())->toBe('danger')
        ->and(Catalogue_Status::no_data_from_worms->getColor())->toBe('danger');
});

it('returns all catalogue status icons', function () {
    expect(Catalogue_Status::checked_accepted->getIcon())->toBe('tabler-circle-check')
        ->and(Catalogue_Status::checked_not_accepted->getIcon())->toBe('tabler-circle-x')
        ->and(Catalogue_Status::not_checked->getIcon())->toBe('tabler-clock')
        ->and(Catalogue_Status::no_data_from_worms->getIcon())->toBe('tabler-database-x');
});

it('derives checked_accepted from accepted worms status', function () {
    expect(Catalogue_Status::fromWormsData(Worms_Status::accepted))->toBe(Catalogue_Status::checked_accepted);
});

it('derives checked_accepted from alternative_representation', function () {
    expect(Catalogue_Status::fromWormsData(Worms_Status::alternative_representation))->toBe(Catalogue_Status::checked_accepted);
});

it('derives checked_not_accepted from unaccepted worms status', function () {
    expect(Catalogue_Status::fromWormsData(Worms_Status::unaccepted))->toBe(Catalogue_Status::checked_not_accepted);
});

it('derives checked_not_accepted from other non-accepted statuses', function () {
    expect(Catalogue_Status::fromWormsData(Worms_Status::nomen_dubium))->toBe(Catalogue_Status::checked_not_accepted)
        ->and(Catalogue_Status::fromWormsData(Worms_Status::deleted))->toBe(Catalogue_Status::checked_not_accepted)
        ->and(Catalogue_Status::fromWormsData(Worms_Status::uncertain))->toBe(Catalogue_Status::checked_not_accepted);
});

it('returns no_data_from_worms for null or empty input', function () {
    expect(Catalogue_Status::fromWormsData(null))->toBe(Catalogue_Status::no_data_from_worms)
        ->and(Catalogue_Status::fromWormsData(''))->toBe(Catalogue_Status::no_data_from_worms);
});

it('accepts string worms status value', function () {
    expect(Catalogue_Status::fromWormsData('accepted'))->toBe(Catalogue_Status::checked_accepted)
        ->and(Catalogue_Status::fromWormsData('unaccepted'))->toBe(Catalogue_Status::checked_not_accepted);
});

it('provides label alias via label method', function () {
    expect(Catalogue_Status::checked_accepted->label())->toBe('Checked & accepted');
});
