<?php

declare(strict_types=1);

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\StagingIntroEvent;
use App\Models\Taxon;
use App\Services\PromoteStagedIntroEvent;

beforeEach(function (): void {
    $this->promoter = new PromoteStagedIntroEvent;
    $this->taxon = Taxon::factory()->create();
});

function stage(array $overrides = []): StagingIntroEvent
{
    return StagingIntroEvent::create(array_merge([
        'taxon_id' => test()->taxon->id,
        'raw_species' => 'Ablennes hians',
        'review_status' => StagingIntroEvent::STATUS_CONFIRMED,
        'nis_status' => NisStatus::NIS,
        'establishment_status' => EstablishmentStatus::Established,
        'first_introduction_year' => 2018,
        'first_country' => 'Syria',
    ], $overrides));
}

it('copies the confirmed med-wide values onto a live record', function (): void {
    $record = $this->promoter->promote(stage());

    expect($record->taxon_id)->toBe($this->taxon->id)
        ->and($record->first_introduction_year)->toBe(2018)
        // Live casts this to an array; staging holds editable plain text.
        ->and($record->first_country)->toBe(['Syria'])
        ->and($record->nis_status)->toBe(NisStatus::NIS)
        ->and($record->establishment_status)->toBe(EstablishmentStatus::Established)
        // A human just reviewed every field — that is what needs_review means.
        ->and($record->needs_review)->toBeFalse();
});

it('writes a subregion record only where the file had something to say', function (): void {
    $staged = stage([
        'cmed_first_arrival_year' => 2018,
        'emed_establishment_status' => EstablishmentStatus::Casual,
        'emed_first_arrival_year' => 2019,
        // WMED and ADRIA left entirely blank.
    ]);

    $record = $this->promoter->promote($staged);
    $subregions = $record->subregionRecords()->get();

    expect($subregions)->toHaveCount(2)
        ->and($subregions->pluck('subregion')->all())
        ->toEqualCanonicalizing([Subregion::CMED, Subregion::EMED]);
});

it('splits a multi-country cell into the live array shape', function (): void {
    $record = $this->promoter->promote(stage(['first_country' => 'Lebanon, Turkey']));

    expect($record->first_country)->toBe(['Lebanon', 'Turkey']);
});

it('is idempotent', function (): void {
    $staged = stage();

    $first = $this->promoter->promote($staged);
    $second = $this->promoter->promote($staged->fresh());

    expect($second->id)->toBe($first->id)
        ->and(IntroEventRecord::count())->toBe(1);
});

it('marks the staged row promoted', function (): void {
    $staged = stage();
    $record = $this->promoter->promote($staged);

    expect($staged->fresh()->review_status)->toBe(StagingIntroEvent::STATUS_PROMOTED)
        ->and($staged->fresh()->promoted_intro_event_id)->toBe($record->id)
        ->and($staged->fresh()->promoted_at)->not->toBeNull();
});

it('refuses a row that is still pending', function (): void {
    $staged = stage(['review_status' => StagingIntroEvent::STATUS_PENDING]);

    expect(fn () => $this->promoter->promote($staged))
        ->toThrow(RuntimeException::class, 'confirmed');
});

it('refuses a row whose species never resolved to a taxon', function (): void {
    $staged = stage(['taxon_id' => null]);

    expect(fn () => $this->promoter->promote($staged))
        ->toThrow(RuntimeException::class, 'taxon');
});
