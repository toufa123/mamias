<?php

declare(strict_types=1);

use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Models\Taxon;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);
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

test('trashed tab query returns only soft-deleted taxa', function () {
    $live = Taxon::factory()->count(2)->create();
    $trashed = Taxon::factory()->create();
    $trashed->delete();

    $tabs = (new ListTaxons)->getTabs();
    $ids = $tabs['trashed']->modifyQuery(TaxonResource::getEloquentQuery())->pluck('id');

    expect($ids)->toContain($trashed->id)
        ->and($ids)->not->toContain($live[0]->id)
        ->and($ids)->not->toContain($live[1]->id);
});

test('non-trashed tab queries exclude soft-deleted taxa', function () {
    $live = Taxon::factory()->count(2)->create();
    $trashed = Taxon::factory()->create();
    $trashed->delete();

    $tabs = (new ListTaxons)->getTabs();

    foreach ($tabs as $key => $tab) {
        if ($key === 'trashed') {
            continue;
        }

        $ids = $tab->modifyQuery(TaxonResource::getEloquentQuery())->pluck('id');
        expect($ids)->not->toContain($trashed->id);
    }

    $allIds = $tabs['all']->modifyQuery(TaxonResource::getEloquentQuery())->pluck('id');
    expect($allIds)->toContain($live[0]->id)
        ->and($allIds)->toContain($live[1]->id);
});

test('soft delete sets deleted_by when authenticated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taxon = Taxon::factory()->create();
    $taxon->delete();

    expect($taxon->fresh()?->deleted_by)->toBe($user->id);
});
