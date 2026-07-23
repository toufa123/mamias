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
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Crumbls\Layup\LayupPlugin;
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
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\Factory;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use JeffersonGoncalves\Filament\RefreshSidebar\RefreshSidebarPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Martin6363\SidebarResize\SidebarResizePlugin;
use Promethys\Revive\RevivePlugin;
use OccTherapist\AdvancedTableExportForFilament\AdvancedTableExportForFilamentPlugin;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use YousefAman\ModalRepeater\ModalRepeaterPlugin;
use Zvizvi\FilamentNotificationsTabs\FilamentNotificationsTabsPlugin;

/**
 * Configures the main MAMIAS Filament administration panel.
 *
 * Registers the panel under the `mamias` id and path, sets up
 * authentication, plugins, navigation groups, theme, colours,
 * middleware, and asset registration.
 */
class MamiasPanelProvider extends PanelProvider
{
    /**
     * Register any panel-specific assets.
     */
    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('app-scripts', Vite::asset('resources/js/app.js')),
        ]);

    }

    /**
     * Build and return the panel configuration.
     */
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
            ->dragAndScroll()
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
                FilamentNotificationCenterPlugin::make(),
                AdvancedTableExportForFilamentPlugin::make()
                    ->maxPdfRows(200)
                    ->maxExportRows(3000)
                    ->previewPerPage(25),
                // ->previewPerPage(25),
                FilamentNotificationsTabsPlugin::make(),
                SidebarResizePlugin::make()
                    ->minWidth(220)
                    ->maxWidth(480),
                SpotlightPlugin::make(),
                RefreshSidebarPlugin::make(),
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
                    ->withSentence('SPA/RAC')
                    ->withLogo(
                        path: '/images/sparac.png',
                        height: 30,
                    )
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
                ModalRepeaterPlugin::make(),
                LayupPlugin::make(),
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
                RevivePlugin::make()
                    ->authorize(fn (): bool => auth()->user()->hasRole('super_admin'))
                    ->navigationGroup('System'),
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
                    ->icon(Heroicon::OutlinedHome),

                Action::make('decomposer')
                    ->label('Decomposer')
                    ->url(fn (): string => url('mamias/decompose'))
                    ->icon(Heroicon::OutlinedCog6Tooth),
            ])

            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Use management'),
                NavigationGroup::make('MAMIAS database'),
                NavigationGroup::make('System'),
                NavigationGroup::make('Settings'),
                NavigationGroup::make('Content management'),
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
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectIfNotPanelUser::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::body.start',
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('filament.mobile-notice'),
            );
    }
}
