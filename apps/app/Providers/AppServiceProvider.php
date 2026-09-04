<?php

namespace App\Providers;

use App\Filament\Auth\Responses\EmailVerificationResponse;
use App\Filament\Auth\Responses\LoginResponse;
use App\Filament\Auth\Responses\RegistrationResponse;
use App\Listeners\LogRoleChangeListener;
use App\Listeners\TaxonImportCompletedListener;
use App\Livewire\ImportWizard;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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

        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => [
                50 => '#f0f9fb',
                100 => '#d9f0f4',
                200 => '#b7e2ea',
                300 => '#85ccd9',
                400 => '#4cafbf',
                500 => '#00899d',
                600 => '#007a8c',
                700 => '#006b7a',
                800 => '#005f6b',
                900 => '#004e59',
                950 => '#00353d',
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
