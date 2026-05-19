<?php

declare(strict_types=1);

use App\Enums\LiteratureType;
use App\Livewire\MyReferences;
use App\Models\Literature;
use App\Models\User;
use App\Notifications\NewLiteratureReferenceNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('scientist', 'web');
    Role::findOrCreate('user', 'web');
});

it('stores correct database notification data', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $regularUser = User::factory()->create();
    $regularUser->assignRole('user');
    $this->actingAs($regularUser);

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', 'DB Notification Test')
        ->set('mountedActions.0.data.full_ref', 'Full reference for db notification test')
        ->set('mountedActions.0.data.type', LiteratureType::ARTICLE->value)
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $literature = Literature::where('short_ref', 'DB Notification Test')->first();
    expect($literature)->not->toBeNull();

    $notification = $admin->notifications()
        ->where('data->title', 'New Reference Submitted')
        ->latest()
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['body'])->toContain($literature->code);
    expect($notification->data['body'])->toContain('submitted by');
});

it('notifies super_admin and scientist when new reference is added', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $scientist = User::factory()->create();
    $scientist->assignRole('scientist');

    $regularUser = User::factory()->create();
    $regularUser->assignRole('user');
    $this->actingAs($regularUser);

    livewire(MyReferences::class)
        ->mountAction('create')
        ->set('mountedActions.0.data.short_ref', 'Notification Test')
        ->set('mountedActions.0.data.full_ref', 'Full reference for notification test')
        ->set('mountedActions.0.data.type', LiteratureType::ARTICLE->value)
        ->callMountedAction()
        ->assertHasNoActionErrors();

    Notification::assertSentTo(
        [$admin, $scientist],
        NewLiteratureReferenceNotification::class
    );

    Notification::assertNotSentTo(
        $regularUser,
        NewLiteratureReferenceNotification::class
    );
});
