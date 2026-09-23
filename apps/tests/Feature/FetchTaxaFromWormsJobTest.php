<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Jobs\FetchEasinIdsJob;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\Taxon;
use App\Models\User;
use App\Services\WormsService;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

it('dispatches the job with taxon ids and user id', function () {
    Queue::fake();

    $taxons = Taxon::factory()->count(3)->create();
    $user = User::factory()->create();

    FetchTaxaFromWormsJob::dispatch(
        $taxons->pluck('id')->all(),
        $user->id,
    );

    Queue::assertPushed(FetchTaxaFromWormsJob::class);
});

it('fetches from worms and updates cache progress to completed', function () {
    $taxons = Taxon::factory()->count(2)->create();
    $user = User::factory()->create();

    $wormsService = Mockery::mock(WormsService::class);
    $wormsService->shouldReceive('getRecordByName')
        ->andReturn(null);
    app()->instance(WormsService::class, $wormsService);

    $job = new FetchTaxaFromWormsJob($taxons->pluck('id')->all(), $user->id);
    app()->call([$job, 'handle']);

    $progress = Cache::get("worms-fetch-progress-{$user->id}");

    expect($progress)
        ->not->toBeNull()
        ->and($progress['status'])->toBe('completed')
        ->and($progress['percentage'])->toBe(100)
        ->and($progress['totals']['not_found'])->toBe(2);
});

it('updates taxon catalogue status to no_data when worms returns nothing', function () {
    $taxon = Taxon::factory()->create();

    $wormsService = Mockery::mock(WormsService::class);
    $wormsService->shouldReceive('getRecordByName')
        ->once()
        ->andReturn(null);
    app()->instance(WormsService::class, $wormsService);

    $job = new FetchTaxaFromWormsJob([$taxon->id], null);
    app()->call([$job, 'handle']);

    $taxon->refresh();
    expect($taxon->catalogue_status)->toBe(Catalogue_Status::no_data_from_worms);
});

it('skips cache update when user id is null', function () {
    $taxon = Taxon::factory()->create();

    $wormsService = Mockery::mock(WormsService::class);
    $wormsService->shouldReceive('getRecordByName')->andReturn(null);
    app()->instance(WormsService::class, $wormsService);

    $job = new FetchTaxaFromWormsJob([$taxon->id], null);
    app()->call([$job, 'handle']);

    expect(Cache::get('worms-fetch-progress-'))->toBeNull();
});

it('marks the progress failed and notifies the user when a sync fails permanently', function (string $jobClass, string $progressKey, string $title) {
    $user = User::factory()->create();

    // A run killed mid-way (timeout, exhausted retries) never reaches handle()'s
    // catch, so the last progress written is still "running".
    Cache::put("{$progressKey}{$user->id}", ['status' => 'running', 'processed' => 40, 'total' => 100, 'percentage' => 40], now()->addHour());

    (new $jobClass([1, 2, 3], $user->id))->failed(new MaxAttemptsExceededException('attempted too many times'));

    expect(Cache::get("{$progressKey}{$user->id}"))
        ->toMatchArray(['status' => 'failed', 'processed' => 40, 'total' => 100, 'error' => 'attempted too many times'])
        ->and($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data['title'])->toBe($title);
})->with([
    'WoRMS' => [FetchTaxaFromWormsJob::class, 'worms-fetch-progress-', 'WoRMS sync failed'],
    'EASIN' => [FetchEasinIdsJob::class, 'easin-fetch-progress-', 'EASIN ID fetch failed'],
]);

it('provides a static duration estimate', function () {
    expect(FetchTaxaFromWormsJob::estimateDuration(30))->toBe('30 seconds')
        ->and(FetchTaxaFromWormsJob::estimateDuration(120))->toBe('2 minutes')
        ->and(FetchTaxaFromWormsJob::estimateDuration(7200))->toBe('2 hours');
});
