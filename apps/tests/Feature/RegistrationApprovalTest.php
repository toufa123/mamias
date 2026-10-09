<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login as LoginPage;
use App\Filament\Pages\Auth\Register as RegisterPage;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Notifications\NewUserAwaitingApproval;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    $this->admin = User::factory()->create()->assignRole('super_admin');
    $this->newUser = User::factory()->unverified()->create()->assignRole('user');

    $this->register = new class extends RegisterPage
    {
        public function notifyAbout(User $user): void
        {
            $this->sendEmailVerificationNotification($user);
        }
    };
});

it('asks super_admins to approve a new account instead of emailing a link', function () {
    config(['auth.registration_approval' => true]);
    Notification::fake();

    $this->register->notifyAbout($this->newUser);

    Notification::assertSentTo($this->admin, NewUserAwaitingApproval::class, fn (NewUserAwaitingApproval $notification): bool => $notification->user->is($this->newUser));
    Notification::assertNothingSentTo($this->newUser);
});

it('keeps emailing the verification link when approval is off', function () {
    config(['auth.registration_approval' => false]);
    Notification::fake();

    $this->register->notifyAbout($this->newUser);

    Notification::assertNotSentTo($this->admin, NewUserAwaitingApproval::class);
    expect(Notification::sentNotifications())->toHaveKey(User::class);
});

it('leaves a newly registered account signed out until it is approved', function () {
    config(['auth.registration_approval' => true, 'honeypot.enabled' => false]);
    Notification::fake();

    Livewire::test(RegisterPage::class)
        ->fillForm([
            'title' => 'Dr',
            'first_name' => 'Waiting',
            'last_name' => 'User',
            'email' => 'waiting@example.com',
            'country' => 'TN',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getLoginUrl());

    $this->assertGuest();
    expect(User::where('email', 'waiting@example.com')->first())
        ->not->toBeNull()
        ->hasVerifiedEmail()->toBeFalse();
});

it('refuses to sign in an account that is awaiting approval', function () {
    config(['auth.registration_approval' => true]);

    Livewire::test(LoginPage::class)
        ->fillForm(['email' => $this->newUser->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

it('signs in an unverified account normally when approval is off', function () {
    config(['auth.registration_approval' => false]);

    Livewire::test(LoginPage::class)
        ->fillForm(['email' => $this->newUser->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($this->newUser);
});

it('signs out an unapproved account that is still logged in', function () {
    config(['auth.registration_approval' => true]);
    $this->actingAs($this->newUser);

    get('/mamias/email-verification/prompt')->assertRedirect(Filament::getLoginUrl());

    $this->assertGuest();
});

it('lists unverified accounts with the email verified filter', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->filterTable('email_verified_at', false)
        ->assertCanSeeTableRecords([$this->newUser])
        ->assertCanNotSeeTableRecords([$this->admin]);
});

it('lets a super_admin verify an account from the users table', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('verifyEmail')->table($this->admin))
        ->callAction(TestAction::make('verifyEmail')->table($this->newUser))
        ->assertNotified('Email verified');

    expect($this->newUser->fresh()->hasVerifiedEmail())->toBeTrue();

    auth()->logout();
    config(['auth.registration_approval' => true]);

    Livewire::test(LoginPage::class)
        ->fillForm(['email' => $this->newUser->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($this->newUser->fresh());
});
