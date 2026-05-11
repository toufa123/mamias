<?php

declare(strict_types=1);

use App\Models\Taxon;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('taxon model uses soft deletes', function () {
    $taxon = Taxon::factory()->create();
    $taxon->delete();

    $this->assertSoftDeleted('taxas', ['id' => $taxon->id]);
});

test('soft-deleted taxon is excluded from default queries', function () {
    $taxon = Taxon::factory()->create();
    $taxon->delete();

    expect(Taxon::find($taxon->id))->toBeNull();
    expect(Taxon::withTrashed()->find($taxon->id))->not->toBeNull();
});

test('soft-deleted taxon can be restored', function () {
    $taxon = Taxon::factory()->create();
    $taxon->delete();

    $taxon->restore();

    expect(Taxon::find($taxon->id))->not->toBeNull();
    expect($taxon->fresh()->deleted_at)->toBeNull();
});

test('soft-deleted taxon can be force deleted', function () {
    $taxon = Taxon::factory()->create();
    $taxon->delete();

    $taxon->forceDelete();

    expect(Taxon::withTrashed()->find($taxon->id))->toBeNull();
});

test('only trashed returns soft-deleted records', function () {
    Taxon::factory()->count(3)->create();
    $trashed = Taxon::factory()->create();
    $trashed->delete();

    expect(Taxon::count())->toBe(3);
    expect(Taxon::onlyTrashed()->count())->toBe(1);
    expect(Taxon::withTrashed()->count())->toBe(4);
});

test('soft delete sets deleted_by when authenticated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taxon = Taxon::factory()->create();
    $taxon->delete();

    expect($taxon->fresh()?->deleted_by)->toBe($user->id);
});
