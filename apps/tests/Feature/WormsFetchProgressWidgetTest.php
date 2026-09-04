<?php

declare(strict_types=1);

use App\Filament\Widgets\WormsFetchProgressWidget;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Cache::forget('worms-fetch-progress-'.$this->user->id);
});

test('shows a preparing state when a sync starts before the worker writes progress', function () {
    livewire(WormsFetchProgressWidget::class)
        ->call('activate') // marks syncing + opens the modal; no progress in cache yet
        ->assertSee('Starting WoRMS sync')
        ->assertSee('Preparing');
});

test('shows the running progress once the worker writes it', function () {
    Cache::put('worms-fetch-progress-'.$this->user->id, [
        'status' => 'running',
        'processed' => 25,
        'total' => 100,
        'percentage' => 25,
        'estimatedTime' => '1 minute',
    ], now()->addHour());

    livewire(WormsFetchProgressWidget::class)
        ->assertSee('Syncing taxonomy from WoRMS')
        ->assertSee('25 of 100');
});

test('the running modal offers an abort button and abort sets the cancel flag', function () {
    Cache::put('worms-fetch-progress-'.$this->user->id, [
        'status' => 'running',
        'processed' => 10,
        'total' => 100,
        'percentage' => 10,
        'estimatedTime' => '1 minute',
    ], now()->addHour());

    livewire(WormsFetchProgressWidget::class)
        ->assertSee('Abort sync')
        ->call('abortWorms');

    expect(Cache::get('worms-fetch-cancel-'.$this->user->id))->toBeTrue();
});

test('an aborted sync shows the cancelled state with resume and close', function () {
    Cache::put('worms-fetch-progress-'.$this->user->id, [
        'status' => 'cancelled',
        'processed' => 40,
        'total' => 100,
        'percentage' => 40,
        'remaining' => 60,
        'remaining_ids' => [101, 102],
        'totals' => ['updated' => 40, 'not_found' => 0, 'missing_aphia_id' => 0],
    ], now()->addHour());

    livewire(WormsFetchProgressWidget::class)
        ->assertSee('Sync aborted')
        ->assertSee('Resume')
        ->assertSee('Close');
});

test('resume re-dispatches the sync for the remaining ids and clears progress', function () {
    Queue::fake();

    Cache::put('worms-fetch-progress-'.$this->user->id, [
        'status' => 'cancelled',
        'processed' => 40,
        'total' => 100,
        'remaining' => 2,
        'remaining_ids' => [101, 102],
    ], now()->addHour());

    livewire(WormsFetchProgressWidget::class)->call('resumeWorms');

    Queue::assertPushed(FetchTaxaFromWormsJob::class);
    expect(Cache::get('worms-fetch-progress-'.$this->user->id))->toBeNull();
});
