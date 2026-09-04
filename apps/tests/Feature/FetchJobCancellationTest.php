<?php

declare(strict_types=1);

use App\Jobs\FetchEasinIdsJob;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\Taxon;
use App\Models\User;
use App\Services\EasinService;
use App\Services\TaxonService;
use App\Services\WormsService;
use Illuminate\Support\Facades\Cache;

test('an EASIN fetch aborts mid-run and records the unprocessed ids for resume', function () {
    $user = User::factory()->create();
    $taxa = Taxon::factory()->count(3)->create();
    $ids = $taxa->pluck('id')->all();

    Cache::forget('easin-fetch-cancel-'.$user->id);

    // Simulate the user hitting "Abort" while the first record is being looked
    // up: the flag flips on, so the loop stops before the second record.
    $this->mock(EasinService::class, function ($mock) use ($user): void {
        $mock->shouldReceive('fetchEasinId')->andReturnUsing(function () use ($user) {
            Cache::put('easin-fetch-cancel-'.$user->id, true, now()->addHour());

            return null;
        });
    });

    (new FetchEasinIdsJob($ids, $user->id))->handle(app(EasinService::class));

    $progress = Cache::get('easin-fetch-progress-'.$user->id);

    expect($progress['status'])->toBe('cancelled')
        ->and($progress['processed'])->toBe(1)
        ->and($progress['remaining'])->toBe(2)
        ->and($progress['remaining_ids'])->toEqualCanonicalizing(array_slice($ids, 1));

    // The cancel flag is cleared so a resumed run isn't killed immediately.
    expect(Cache::get('easin-fetch-cancel-'.$user->id))->toBeNull();
});

test('a WoRMS sync aborts mid-record and records the unprocessed ids for resume', function () {
    $user = User::factory()->create();
    $taxa = Taxon::factory()->count(3)->create();
    $ids = $taxa->pluck('id')->all();

    Cache::forget('worms-fetch-cancel-'.$user->id);

    // Simulate the user aborting while the first record is being looked up: the
    // WoRMS lookup flips the flag and returns "not found" (no further calls).
    $this->mock(WormsService::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getRecordByName')->andReturnUsing(function () use ($user) {
            Cache::put('worms-fetch-cancel-'.$user->id, true, now()->addHour());

            return null;
        });
    });

    (new FetchTaxaFromWormsJob($ids, $user->id))->handle(app(TaxonService::class));

    $progress = Cache::get('worms-fetch-progress-'.$user->id);

    expect($progress['status'])->toBe('cancelled')
        ->and($progress['processed'])->toBe(1)
        ->and($progress['remaining'])->toBe(2)
        ->and($progress['remaining_ids'])->toEqualCanonicalizing(array_slice($ids, 1));

    expect(Cache::get('worms-fetch-cancel-'.$user->id))->toBeNull();
});
