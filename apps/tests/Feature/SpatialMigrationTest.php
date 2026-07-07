<?php

use App\Models\IntroEventRecord;
use App\Models\NisSuggestion;
use App\Models\Occurrence;
use App\Models\Taxon;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses()->group('spatial');

beforeEach(function () {
    DB::statement('TRUNCATE occurrences, intro_event_records, literatures, taxas, nis_suggestions, nis_suggestion_literature, pathway_records, subregion_records, recycle_bin_items CASCADE');
});

function makeOccurrence(array $overrides = []): Occurrence
{
    $user = User::factory()->create();
    $taxon = Taxon::factory()->create();
    $introEventRecord = IntroEventRecord::factory()->create([
        'taxon_id' => $taxon->id,
        'literature_id' => null,
    ]);

    return Occurrence::factory()->create(array_merge([
        'user_id' => $user->id,
        'intro_event_record_id' => $introEventRecord->id,
    ], $overrides));
}

it('adds location_point column to occurrences table', function () {
    expect(Schema::hasColumn('occurrences', 'location_point'))->toBeTrue();
});

it('adds location_point column to nis_suggestions table', function () {
    expect(Schema::hasColumn('nis_suggestions', 'location_point'))->toBeTrue();
});

it('has spatial GIST index on occurrences', function () {
    $indexes = DB::select("
        SELECT indexname FROM pg_indexes
        WHERE tablename = 'occurrences'
        AND indexdef ILIKE '%gist%location_point%'
    ");

    expect($indexes)->not->toBeEmpty();
});

it('has spatial GIST index on nis_suggestions', function () {
    $indexes = DB::select("
        SELECT indexname FROM pg_indexes
        WHERE tablename = 'nis_suggestions'
        AND indexdef ILIKE '%gist%location_point%'
    ");

    expect($indexes)->not->toBeEmpty();
});

it('syncs location_point from first coordinate on save', function () {
    $occurrence = makeOccurrence([
        'location' => [['lat' => 35.0, 'lng' => 25.0]],
    ]);

    $point = $occurrence->fresh()->location_point;

    expect($point)->toBeInstanceOf(Point::class);
    expect($point->getY())->toBe(35.0);
    expect($point->getX())->toBe(25.0);
});

it('syncs location_point on nis_suggestion save', function () {
    $suggestion = NisSuggestion::factory()->create();

    expect($suggestion->fresh()->location_point)->toBeInstanceOf(Point::class);
});

it('scope near finds occurrences within 5km', function () {
    $central = makeOccurrence([
        'location' => [['lat' => 36.0, 'lng' => 14.0]],
    ]);
    $nearby = makeOccurrence([
        'location' => [['lat' => 36.01, 'lng' => 14.01]],
    ]);
    $far = makeOccurrence([
        'location' => [['lat' => 38.0, 'lng' => 12.0]],
    ]);

    $results = Occurrence::near(36.0, 14.0, 5_000)->get();

    expect($results->pluck('id'))->toContain($central->id, $nearby->id);
    expect($results->pluck('id'))->not->toContain($far->id);
});

it('scope withinBoundingBox filters correctly', function () {
    $inside = makeOccurrence([
        'location' => [['lat' => 35.5, 'lng' => 14.5]],
    ]);
    $outside = makeOccurrence([
        'location' => [['lat' => 40.0, 'lng' => 20.0]],
    ]);

    $results = Occurrence::withinBoundingBox(34.0, 13.0, 36.0, 16.0)->get();

    expect($results->pluck('id'))->toContain($inside->id);
    expect($results->pluck('id'))->not->toContain($outside->id);
});

it('scope orderByDistance returns nearest first', function () {
    makeOccurrence([
        'location' => [['lat' => 40.0, 'lng' => 20.0]],
    ]);
    makeOccurrence([
        'location' => [['lat' => 36.01, 'lng' => 14.01]],
    ]);

    $results = Occurrence::orderByDistance(36.0, 14.0)->take(10)->get();

    expect((float) $results->first()->location_point->getY())->toBe(36.01);
});

it('scope withDistanceFrom computes distance in meters', function () {
    $occurrence = makeOccurrence([
        'location' => [['lat' => 36.0, 'lng' => 14.0]],
    ]);

    $result = Occurrence::withDistanceFrom(36.0, 14.0)->find($occurrence->id);

    expect($result->distance_meters)->toBeLessThan(1);
});
