<?php

namespace App\Providers;

use App\Filament\Auth\Responses\EmailVerificationResponse;
use App\Filament\Auth\Responses\LoginResponse;
use App\Filament\Auth\Responses\RegistrationResponse;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use App\Listeners\TaxonImportCompletedListener;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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

        Livewire::component('filament-import-wizard', \App\Livewire\ImportWizard::class);

        Event::listen(ImportCompleted::class, TaxonImportCompletedListener::class);

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
