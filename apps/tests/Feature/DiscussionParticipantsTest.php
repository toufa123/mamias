<?php

use App\Filament\Resources\Literatures\Pages\ListLiteratures;
use App\Models\Literature;
use App\Models\Taxon;
use App\Models\User;
use App\Notifications\LiteratureCommented;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Kirschbaum\Commentions\Actions\SaveComment;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->create()->assignRole('super_admin');
    $this->scientist = User::factory()->create()->assignRole('scientist');
    $this->actingAs($this->admin);
});

it('sets who follows a discussion, adding and removing participants', function () {
    $literature = Literature::factory()->pending()->create();
    $literature->subscribe($this->admin);

    livewire(ListLiteratures::class)
        ->callAction(TestAction::make('discussion_participants')->table($literature), ['participants' => [$this->scientist->id]]);

    expect($literature->getSubscribers()->modelKeys())->toBe([$this->scientist->id]);
});

it('notifies species discussion participants, not the author', function () {
    $taxon = Taxon::factory()->create();
    $taxon->subscribe($this->scientist);

    SaveComment::run($taxon, $this->admin, 'Does the Levantine population fit?');

    expect($this->scientist->notifications()->count())->toBe(1)
        ->and($this->scientist->notifications()->first()->data['title'])->toContain($taxon->scientificname)
        ->and($this->admin->notifications()->count())->toBe(0);
});

it('adds reference discussion participants to the usual routing, once each', function () {
    Notification::fake();
    $submitter = User::factory()->create();
    $literature = Literature::factory()->pending()->create(['created_by' => $submitter->id]);
    $literature->subscribe($this->scientist);
    $literature->subscribe($submitter);

    SaveComment::run($literature, $this->admin, 'Which DOI?');

    Notification::assertSentToTimes($submitter, LiteratureCommented::class, 1);
    Notification::assertSentToTimes($this->scientist, LiteratureCommented::class, 1);
    Notification::assertNotSentTo($this->admin, LiteratureCommented::class);
});
