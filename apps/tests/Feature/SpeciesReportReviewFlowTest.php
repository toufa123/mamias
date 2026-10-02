<?php

use App\Enums\OccurrenceStatus;
use App\Livewire\MySpeciesReports;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\User;
use App\Notifications\OccurrenceSubmitted;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Notification::fake();

    $this->reporter = User::factory()->create();
    $this->moderator = User::factory()->create()->assignRole('scientist');

    $this->actingAs($this->reporter);
});

it('tells moderators when a report is submitted', function () {
    livewire(MySpeciesReports::class)
        ->callAction('create', [
            'intro_event_record_id' => IntroEventRecord::factory()->create()->id,
            'observed_at' => '2026-08-01 10:00',
            'location' => ['lat' => 36.5, 'lng' => 14.2],
        ])
        ->assertHasNoFormErrors();

    Notification::assertSentTo($this->moderator, OccurrenceSubmitted::class, fn (OccurrenceSubmitted $notification): bool => ! $notification->isResubmission);
    Notification::assertNotSentTo($this->reporter, OccurrenceSubmitted::class);
});

it('sends a rejected report back to the queue when the reporter revises it', function () {
    $occurrence = Occurrence::factory()->for($this->reporter)->create([
        'status' => OccurrenceStatus::REJECTED,
        'moderation_notes' => 'Photo missing',
    ]);

    livewire(MySpeciesReports::class)
        ->callAction(TestAction::make('edit')->table($occurrence), ['depth' => 4])
        ->assertHasNoFormErrors();

    expect($occurrence->refresh())
        ->status->toBe(OccurrenceStatus::PENDING)
        ->moderation_notes->toBe('Photo missing');

    Notification::assertSentTo($this->moderator, OccurrenceSubmitted::class, fn (OccurrenceSubmitted $notification): bool => $notification->isResubmission);
});

it('does not re-notify moderators for edits to a pending report', function () {
    $occurrence = Occurrence::factory()->for($this->reporter)->create(['status' => OccurrenceStatus::PENDING]);

    livewire(MySpeciesReports::class)
        ->callAction(TestAction::make('edit')->table($occurrence), ['depth' => 4])
        ->assertHasNoFormErrors();

    Notification::assertNotSentTo($this->moderator, OccurrenceSubmitted::class);
});

it('lets the reporter withdraw only a pending report', function () {
    $pending = Occurrence::factory()->for($this->reporter)->create(['status' => OccurrenceStatus::PENDING]);
    $approved = Occurrence::factory()->for($this->reporter)->create(['status' => OccurrenceStatus::APPROVED]);

    livewire(MySpeciesReports::class)
        ->assertActionHidden(TestAction::make('withdraw')->table($approved))
        ->callAction(TestAction::make('withdraw')->table($pending));

    expect(Occurrence::find($pending->id))->toBeNull()
        ->and(Occurrence::find($approved->id))->not->toBeNull();
});
