<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EmailVerificationPrompt;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\ComposerDependencies;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HealthCheckResults;
use App\Filament\Pages\NpmDependencies;
use App\Filament\Widgets\MamiasInfoWidget;
use App\Http\Middleware\RedirectIfNotPanelUser;
use AzGasim\FilamentUnsavedChangesModal\FilamentUnsavedChangesModalPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BinaryBuilds\CommandRunner\CommandRunnerPlugin;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Elemind\FilamentECharts\FilamentEChartsPlugin;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JeffersonGoncalves\Filament\RefreshSidebar\RefreshSidebarPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Promethys\Revive\RevivePlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class MamiasPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('app-scripts', Vite::asset('resources/js/app.js')),
        ]);

        Gate::define('manage-resource-locks', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('manage-resource-locks-audit', fn ($user) => $user->hasRole('super_admin'));
    }

    public function panel(Panel $panel): Panel
    {

        return $panel
            ->default()
            ->id('mamias')
            ->path('mamias')
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName('Filament Demo')
            ->brandLogo(asset('images/mamias.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/favicon.png'))
            ->maxContentWidth(Width::Full)
            ->spa(hasPrefetching: true)
            ->spaUrlExceptions(fn (): array => [
                url('/mamias'),
                url('/'),
            ])
            ->maxContentWidth(Width::Full)
            ->unsavedChangesAlerts()
            ->sidebarWidth('15rem')
            ->globalSearch(false)
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/mamias/theme.css')
            ->font('Roboto', provider: GoogleFontProvider::class)
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification(EmailVerificationPrompt::class)
            ->plugins([
                RevivePlugin::make()
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin'))
                    ->navigationGroup('Settings') // Group the page under a custom sidebar section
                    ->navigationIcon('heroicon-o-archive-box-arrow-down')
                    ->activeNavigationIcon('heroicon-o-archive-box-arrow-down')
                    ->navigationSort(1)
                    ->navigationLabel('Custom Label')
                    ->title('Custom Title')
                    ->slug('custom-slug'),
                RefreshSidebarPlugin::make(),
                //                FilamentSpatieLaravelBackupPlugin::make()
                //                    ->navigationIcon('heroicon-o-cpu-chip')
                //                    ->navigationLabel('Backups')
                //                    ->navigationGroup('system')
                //                    ->navigationSort(3),
                ResourceLockPlugin::make()
                    ->limitedAccessToResourceLockManager(true)
                    ->gate('manage-resource-locks')
                    ->auditLimitedAccess(true)
                    ->auditGate('manage-resource-locks-audit'),
                CommandRunnerPlugin::make()
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin'))
                    ->navigationGroup('System')
                    ->navigationIcon('tabler-alert-triangle'),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class)
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin')),
                EnvironmentIndicatorPlugin::make()
                    ->color(fn () => match (app()->environment()) {
                        'production' => null,
                        'staging' => Color::Hex('#FF6B35'),
                        default => Color::Hex('#018d9a'),
                    })
                    ->showDebugModeWarningInProduction(),
                Lockscreen::make()
                    ->usingCustomTableColumns('email',
                        'password') // Use custom table columns. Default:  email, password.
                    ->enableRateLimit() // Enable rate limit for the lockscreen. Default: Enable, 5 attempts in 1 minute.
                    // ->setUrl() // Customize the lockscreen url.
                    ->enableIdleTimeout() // Enable auto lock during idle time. Default: Enable, 30 minutes.
                    ->disableDisplayName('name') // Display the name of the user based on the attribute supplied. Default: name
                    ->icon('tabler-file-text') // Customize the icon of the lockscreen.
                    ->enablePlugin(), // Enable the lockscreen plugin.
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'atef.ouerghi@spa-rac.org',
                        'Scientist' => 'scientist@mamias.local',
                        'Public User' => 'atef.ouerghi@gmail.com',
                    ]),
                AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(true)
                    ->mobileFormPanelPosition('top')
                    ->formPanelPosition('right')
                    ->formPanelWidth('70%')
                    ->emptyPanelView('auth.empty-panel'),
                EasyFooterPlugin::make()
                    ->withSentence(new HtmlString('<img src="'.asset('images/sparac.png').'" style="margin-right:.4rem;" alt="Laravel Logo" width="150" height="20"> SPA/RAC'))
                    ->withBorder()
                    ->withLinks([
                        ['title' => 'Legal notice', 'url' => '#'],
                        ['title' => 'Terms of use', 'url' => '#'],
                        ['title' => 'Cookies policy', 'url' => '#'],
                    ]),
                FilamentEChartsPlugin::make(),
                FilamentClearCachePlugin::make(),
                FilamentUnsavedChangesModalPlugin::make()
                    ->modalWidth('xl')
                    ->modalIcon('OutlinedExclamationTriangle')
                    ->modalIconColor('danger')
                    ->stayButtonColor('gray')
                    ->leaveButtonColor('warning'),
                FilamentShieldPlugin::make()
                    ->navigationGroup('Use management')
                    ->navigationSort(-1)
                    ->navigationIcon('tabler-shield-check')         // string|Closure|null
                    ->activeNavigationIcon('tabler-shield-check')
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->colors([
                'primary' => [
                    50 => '#f0f9fb',
                    100 => '#d9f0f4',
                    200 => '#b7e2ea',
                    300 => '#85ccd9',
                    400 => '#4cafbf',
                    500 => '#00899d', // ← Teal exact du logo
                    600 => '#007a8c',
                    700 => '#006b7a', // ← Survol actif
                    800 => '#005f6b',
                    900 => '#004e59',
                    950 => '#00353d',
                ],
                'gray' => Color::Slate, // ou un slate légèrement teinté
                'danger' => Color::Rose,
                'info' => [
                    50 => '#eff6ff',
                    100 => '#dbeafe',
                    200 => '#bfdbfe',
                    300 => '#93c5fd',
                    400 => '#60a5fa',
                    500 => '#005f98', // ← Bleu du logo
                    600 => '#004e7c',
                    700 => '#003d61',
                    800 => '#003070', // ← Bleu profond du logo
                    900 => '#00254a',
                    950 => '#001838',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])

            ->userMenuItems([
                Action::make('home')
                    ->label('Public site')
                    ->url(fn (): string => url('/'))
                    ->icon('heroicon-o-home'),
                Action::make('decomposer')
                    ->label('Decomposer')
                    ->url(fn (): string => url('mamias/decompose'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ])

            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Use management'),
                NavigationGroup::make('MAMIAS database'),
                NavigationGroup::make('System'),
                NavigationGroup::make('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                ComposerDependencies::class,
                NpmDependencies::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
                MamiasInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectIfNotPanelUser::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
