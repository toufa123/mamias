<?php

declare(strict_types=1);

use App\Enums\LiteratureStatus;
use App\Filament\Resources\Literatures\Pages\EditLiterature;
use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Livewire\MyReferences;
use App\Models\Literature;
use App\Models\User;
use App\Notifications\LiteratureCommented;
use App\Notifications\LiteratureReviewed;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Illuminate\Support\Facades\Notification;
use Kirschbaum\Commentions\Actions\SaveComment;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('user', 'web');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

it('renders the literature list page with tabs', function () {
    livewire(ListLiteratures::class)->assertSuccessful();
});

it('shows pending references in the pending tab', function () {
    $pending = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$pending]);
});

it('approves a pending reference', function () {
    $literature = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('approve')->table($literature));

    expect($literature->fresh()->status)->toBe(LiteratureStatus::APPROVED);
});

it('rejects a pending reference with a reason, and tells the submitter', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $literature = Literature::factory()->pending()->create(['created_by' => $submitter->id]);

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('reject')->table($literature), ['review_comment' => 'Duplicate of mamias000012.'])
        ->assertHasNoActionErrors()
        ->assertNotified('Reference rejected');

    $literature->refresh();

    expect($literature->status)->toBe(LiteratureStatus::REJECTED)
        ->and($literature->review_comment)->toBe('Duplicate of mamias000012.')
        ->and($literature->reviewed_by)->toBe($this->admin->id)
        ->and($literature->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($submitter, LiteratureReviewed::class, function (LiteratureReviewed $notification) use ($submitter) {
        return str_contains(implode(' ', $notification->toMail($submitter)->introLines), 'Duplicate of mamias000012.');
    });
});

it('requires a reason to reject', function () {
    $literature = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('reject')->table($literature), ['review_comment' => ''])
        ->assertHasActionErrors(['review_comment' => 'required']);

    expect($literature->fresh()->status)->toBe(LiteratureStatus::PENDING);
});

it('approves with an optional comment and logs the decision', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $literature = Literature::factory()->pending()->create(['created_by' => $submitter->id]);

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('approve')->table($literature), ['review_comment' => 'Year corrected to 2019.']);

    expect($literature->fresh())
        ->status->toBe(LiteratureStatus::APPROVED)
        ->review_comment->toBe('Year corrected to 2019.');

    Notification::assertSentTo($submitter, LiteratureReviewed::class);

    expect(Activity::where('subject_id', $literature->id)->where('event', 'approved')->first()?->getProperty('review_comment'))
        ->toBe('Year corrected to 2019.');
});

it('does not notify a moderator about their own reference', function () {
    Notification::fake();

    $literature = Literature::factory()->pending()->create(['created_by' => $this->admin->id]);

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('approve')->table($literature));

    Notification::assertNotSentTo($this->admin, LiteratureReviewed::class);
});

it('reviews from the edit page too', function () {
    $literature = Literature::factory()->pending()->create();

    livewire(EditLiterature::class, ['record' => $literature->id])
        ->callAction('reject', ['review_comment' => 'No bibliographic details.'])
        ->assertHasNoActionErrors();

    expect($literature->fresh()->status)->toBe(LiteratureStatus::REJECTED);
});

it('shows the reviewer comment to the submitter in My references', function () {
    $submitter = User::factory()->create();
    $literature = Literature::factory()->create([
        'created_by' => $submitter->id,
        'status' => LiteratureStatus::REJECTED,
        'review_comment' => 'Please add the DOI.',
    ]);

    $this->actingAs($submitter);

    livewire(MyReferences::class)
        ->loadTable()
        ->assertDontSee('Please add the DOI.')
        ->mountTableAction('showReviewComment', $literature)
        ->assertSet('mountedActions.0.name', 'showReviewComment');

    livewire(MyReferences::class)
        ->loadTable()
        ->mountTableAction('view', $literature)
        ->assertMountedActionModalSee('Please add the DOI.');
});

it('offers only the opposite decision once a reference is decided', function () {
    $approved = Literature::factory()->approved()->create();
    $rejected = Literature::factory()->rejected()->create();

    livewire(ListLiteratures::class)
        ->assertActionHidden(TestAction::make('approve')->table($approved))
        ->assertActionVisible(TestAction::make('reject')->table($approved))
        ->assertActionHasLabel(TestAction::make('reject')->table($approved), 'Re-reject')
        ->assertActionHidden(TestAction::make('reject')->table($rejected))
        ->assertActionHasLabel(TestAction::make('approve')->table($rejected), 'Re-approve');
});

it('reverses an approval with a reason', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $literature = Literature::factory()->approved()->create(['created_by' => $submitter->id]);

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('reject')->table($literature), ['review_comment' => 'Retracted by the journal.']);

    expect($literature->fresh())
        ->status->toBe(LiteratureStatus::REJECTED)
        ->review_comment->toBe('Retracted by the journal.');

    Notification::assertSentTo($submitter, LiteratureReviewed::class);
});
it('routes discussion comments to the other side', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $literature = Literature::factory()->pending()->create(['created_by' => $submitter->id]);

    // Unreviewed: the submitter's comment reaches every moderator.
    SaveComment::run($literature, $submitter, 'Is the year right?');
    Notification::assertSentTo($this->admin, LiteratureCommented::class);
    Notification::assertNotSentTo($submitter, LiteratureCommented::class);

    // A moderator's reply reaches the submitter only.
    SaveComment::run($literature, $this->admin, 'Yes, 2019.');
    Notification::assertSentTo($submitter, LiteratureCommented::class);

    // Once reviewed, the submitter's comments go to that reviewer alone.
    $otherModerator = User::factory()->create();
    $otherModerator->assignRole('super_admin');
    $literature->update(['reviewed_by' => $this->admin->id]);

    SaveComment::run($literature, $submitter, 'Thanks!');
    Notification::assertNotSentTo($otherModerator, LiteratureCommented::class);
});

it('offers no resubmission: rejecting a reference is final', function () {
    $submitter = User::factory()->create();
    $rejected = Literature::factory()->rejected()->create(['created_by' => $submitter->id]);

    $this->actingAs($submitter);

    livewire(MyReferences::class)
        ->loadTable()
        ->assertTableActionDoesNotExist('resubmit', record: $rejected)
        ->assertSee('1 reference was not accepted.');
});

it('opens the discussion from the panel and from My references', function () {
    $submitter = User::factory()->create();
    $literature = Literature::factory()->pending()->create(['created_by' => $submitter->id]);

    livewire(ListLiteratures::class)
        ->loadTable()
        ->mountTableAction('commentList', $literature)
        ->assertHasNoErrors()
        ->assertSet('mountedActions.0.name', 'commentList')
        ->assertSet('mountedActions.0.context.recordKey', (string) $literature->id);

    $this->actingAs($submitter);

    livewire(MyReferences::class)
        ->loadTable()
        ->mountTableAction('commentList', $literature)
        ->assertHasNoErrors()
        ->assertSet('mountedActions.0.name', 'commentList')
        ->assertSet('mountedActions.0.context.recordKey', (string) $literature->id);
});

it('shows and searches the submitter', function () {
    $alice = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Marine']);
    $bob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Coral']);
    $fromAlice = Literature::factory()->create(['created_by' => $alice->id]);
    $fromBob = Literature::factory()->create(['created_by' => $bob->id]);

    $alice->assignRole('scientist');
    $bob->assignRole('user');

    livewire(ListLiteratures::class)
        ->loadTable()
        ->assertTableColumnStateSet('creator.name', 'Alice Marine', $fromAlice)
        ->assertTableColumnExists('creator.name', fn (TextColumn $column): bool => $column->getTooltip() === 'Scientist', $fromAlice)
        ->assertTableColumnExists('creator.name', fn (TextColumn $column): bool => $column->getTooltip() === 'Public user', $fromBob)
        ->searchTable('Alice')
        ->assertCanSeeTableRecords([$fromAlice])
        ->assertCanNotSeeTableRecords([$fromBob]);
});

it('views a reference with its review trail', function () {
    $submitter = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Marine']);
    $literature = Literature::factory()->create([
        'created_by' => $submitter->id,
        'status' => LiteratureStatus::REJECTED,
        'review_comment' => 'Wrong year.',
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => now(),
    ]);

    livewire(ListLiteratures::class)
        ->loadTable()
        ->mountTableAction('view', $literature)
        ->assertHasNoErrors()
        ->assertSchemaComponentStateSet('creator.name', 'Alice Marine')
        ->assertSchemaComponentStateSet('reviewer.name', $this->admin->name)
        ->assertSchemaComponentStateSet('review_comment', 'Wrong year.');
});

it('opens the rejection reason from the status badge', function () {
    $rejected = Literature::factory()->create([
        'status' => LiteratureStatus::REJECTED,
        'review_comment' => 'Not a primary source.',
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => now(),
    ]);
    $pending = Literature::factory()->pending()->create();

    livewire(ListLiteratures::class)
        ->set('activeTab', 'all')
        ->loadTable()
        ->assertTableColumnStateSet('status', LiteratureStatus::REJECTED, $rejected)
        ->assertTableActionHidden('showReviewComment', $pending)
        ->mountTableAction('showReviewComment', $rejected)
        ->assertSet('mountedActions.0.name', 'showReviewComment');
});

it('labels a user with several roles by the highest one', function () {
    Role::findOrCreate('scientist', 'web');
    $user = User::factory()->create();
    $user->assignRole(['user', 'scientist']);

    expect($user->primaryRoleName())->toBe('scientist')
        ->and(User::factory()->create()->primaryRoleName())->toBeNull();
});

it('renders the review pop-up in the design-system colours', function () {
    $rejected = Literature::factory()->create([
        'status' => LiteratureStatus::REJECTED,
        'review_comment' => 'Not a primary source.',
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => '2026-09-25 14:30:00',
    ]);
    $approved = Literature::factory()->approved()->create(['review_comment' => 'Year corrected.']);

    // Action modals render as a Livewire partial outside html(), so the body view is rendered directly.
    $html = view('filament.literatures.review-comment', ['record' => $rejected, 'reviewerLabel' => $this->admin->name])->render();

    expect($html)
        ->toContain('Rejection reason', 'Not a primary source.', $rejected->code, $this->admin->name, '2026-09-25 14:30')
        ->toContain('var(--status-invasive-fill,var(--danger-50))')
        ->not->toContain('dark:')
        ->not->toContain('rounded');

    expect(view('filament.literatures.review-comment', ['record' => $approved])->render())
        ->toContain('Reviewer comment', 'Year corrected.', 'var(--status-unresolved-fill,var(--gray-50))')
        ->not->toContain('status-invasive');
});

it('keeps the actions menu at the end of the row', function () {
    $table = livewire(ListLiteratures::class)->instance()->getTable();

    expect($table->getRecordActionsPosition())->toBe(RecordActionsPosition::AfterColumns)
        ->and($table->getColumn('short_ref')->isVisible())->toBeTrue()
        ->and($table->getColumn('status')->isVisible())->toBeTrue();
});

it('filters references by year, submitter and comments awaiting a reply', function () {
    $submitter = User::factory()->create();
    $waiting = Literature::factory()->pending()->create(['year' => 2014, 'created_by' => $submitter->id]);
    $other = Literature::factory()->pending()->create(['year' => 2009]);
    SaveComment::run($waiting, $submitter, 'Which DOI should I use?');

    livewire(ListLiteratures::class)
        ->loadTable()
        ->filterTable('year', 2014)
        ->assertCanSeeTableRecords([$waiting])
        ->assertCanNotSeeTableRecords([$other])
        ->resetTableFilters()
        ->filterTable('created_by', $submitter->id)
        ->assertCanSeeTableRecords([$waiting])
        ->assertCanNotSeeTableRecords([$other])
        ->resetTableFilters()
        ->filterTable('unanswered_comments')
        ->assertCanSeeTableRecords([$waiting])
        ->assertCanNotSeeTableRecords([$other]);
});
