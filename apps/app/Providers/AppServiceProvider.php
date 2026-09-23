<?php

namespace App\Providers;

use App\Filament\Auth\Responses\EmailVerificationResponse;
use App\Filament\Auth\Responses\LoginResponse;
use App\Filament\Auth\Responses\RegistrationResponse;
use App\Listeners\LogRoleChangeListener;
use App\Listeners\TaxonImportCompletedListener;
use App\Livewire\ImportWizard;
use App\Models\User;
use App\Services\TaxonMatcher;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

/**
 * Core service provider for the MAMIAS application.
 *
 * Registers custom Filament auth responses, IDE helper debugbar
 * (local only), application-wide colour palette, Livewire components,
 * event listeners, and server health checks.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(RegistrationResponseContract::class, RegistrationResponse::class);
        $this->app->bind(EmailVerificationResponseContract::class, EmailVerificationResponse::class);

        // Singleton because it indexes the whole catalogue (accepted names
        // plus every recorded synonym) on first use. An import resolves a
        // thousand-odd names in one pass; rebuilding that index per row would
        // turn one query into one per row.
        $this->app->singleton(TaxonMatcher::class);

        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Surfaces N+1 access outside production. Filament tables eager-load in
        // their own modifyQueryUsing(); anything that still lazy-loads is a bug
        // we want raised before it is paid for per row in production.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Throwing is only useful where something is watching: the suite fails
        // on a violation, so it cannot be ignored. Browsing locally logs instead
        // — a page that has always lazy-loaded should report itself, not 500 and
        // block whatever else was being worked on.
        if (! $this->app->runningUnitTests()) {
            Model::handleLazyLoadingViolationUsing(
                fn (Model $model, string $relation) => logger()->warning(
                    'Lazy loaded relation ['.$relation.'] on ['.$model::class.'].'
                )
            );
        }

        $invasive = [
            50 => '#feecea', 100 => '#fddcd8', 200 => '#f5c9c4', 300 => '#ff9e93',
            400 => '#e5665a', 500 => '#d33d2f', 600 => '#b42318', 700 => '#912018',
            800 => '#7a1c15', 900 => '#5e1712', 950 => '#3a1512',
        ];

        // Same ramp as MamiasPanelProvider::panel() and --mamias-teal-* in
        // app.css — this one covers Filament components rendered outside a
        // panel. All three must move together; see DESIGN-SYSTEM.md.
        FilamentColor::register([
            'primary' => [
                50 => '#eaf6f8',
                100 => '#d3edf1',
                200 => '#a9dbe3',
                300 => '#6fc3d0',
                400 => '#29a3b7',
                500 => '#078da0', // logo teal — never under white text
                600 => '#056273', // fills under white text, 7.08:1
                700 => '#044e5c',
                800 => '#033b46',
                900 => '#022c35',
                950 => '#011e24',
            ],
            // Status vocabulary from DESIGN-SYSTEM.md: 50 is the documented fill,
            // 200 the border, 600 the text, 300 the dark text, 950 the dark fill.
            // 500 is kept under 4.5:1 on the fill so Filament's badge text lands
            // on 600. "Unresolved" is the gray ramp itself — enums use 'gray'.
            // One red: 'danger' (rejected, destructive) is the invasive ramp, so
            // red means "bad" everywhere instead of two near-identical reds.
            'invasive' => $invasive,
            'danger' => $invasive,
            'established' => [
                50 => '#ffefd4', 100 => '#ffe3b3', 200 => '#f3ddb6', 300 => '#ffc26b',
                400 => '#f59e2a', 500 => '#d97706', 600 => '#b45309', 700 => '#92400e',
                800 => '#78350f', 900 => '#5c2a0c', 950 => '#3a2610',
            ],
            'casual' => [
                50 => '#e3edf5', 100 => '#d2e3f0', 200 => '#bfd4e4', 300 => '#7fc1ef',
                400 => '#3f95cf', 500 => '#1a74b0', 600 => '#00558c', 700 => '#004672',
                800 => '#003858', 900 => '#002b44', 950 => '#0e2c44',
            ],
            'verified' => [
                50 => '#edf8f2', 100 => '#dcf0e5', 200 => '#c8e4d6', 300 => '#79d3a6',
                400 => '#3fae78', 500 => '#2a8a5c', 600 => '#1f6b49', 700 => '#19573b',
                800 => '#14452f', 900 => '#103724', 950 => '#0f2e22',
            ],
            // Not in the species vocabulary proper: NisStatus::RangeExpansion,
            // the one value meaning "not introduced". Violet is used nowhere else.
            'native' => [
                50 => '#f0edf7', 100 => '#e5e0f1', 200 => '#d9d2ea', 300 => '#b9a9e8',
                400 => '#9886c9', 500 => '#7a68ae', 600 => '#5b4b8a', 700 => '#4a3d72',
                800 => '#3b3159', 900 => '#2e2645', 950 => '#231b3a',
            ],
        ]);

        Livewire::component('filament-import-wizard', ImportWizard::class);

        // Embed the MAMIAS logo inline (CID "mamias-logo", referenced by the mail
        // header) so it renders reliably without depending on a publicly reachable
        // asset URL and isn't blocked as a remote image by mail clients.
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $logo = public_path('images/mamias.png');

            if (is_file($logo)) {
                $event->message->embedFromPath($logo, 'mamias-logo');
            }
        });

        Event::listen(ImportCompleted::class, TaxonImportCompletedListener::class);

        // unifilemanager/filament-file-manager's DefaultFileManagerAuthorizer
        // checks $user->can('manageFileManager'), which resolves through this
        // gate — the package requires this exact ability name.
        Gate::define('manageFileManager', fn (User $user): bool => $user->hasRole('super_admin'));

        Event::listen(
            [RoleAttachedEvent::class, RoleDetachedEvent::class, PermissionAttachedEvent::class, PermissionDetachedEvent::class],
            LogRoleChangeListener::class,
        );

        Health::checks([
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            DatabaseCheck::new(),
            RedisCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            CacheCheck::new(),
            QueueCheck::new(),
        ]);
    }
}
