<?php

namespace App\Providers\Filament;

use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Pages\Auth\EmailVerificationPrompt;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\ComposerDependencies;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\FileManager;
use App\Filament\Pages\HealthCheckResults;
use App\Filament\Pages\NpmDependencies;
use App\Filament\Widgets\MamiasInfoWidget;
use App\Http\Middleware\RedirectIfNotPanelUser;
use AzGasim\FilamentUnsavedChangesModal\FilamentUnsavedChangesModalPlugin;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BinaryBuilds\CommandRunner\CommandRunnerPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Crumbls\Layup\LayupPlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Elemind\FilamentECharts\FilamentEChartsPlugin;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\BunnyFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Heyosseus\Vacuum\Filament\VacuumPlugin;
use Illuminate\Contracts\View\Factory;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use JeffersonGoncalves\Filament\RefreshSidebar\RefreshSidebarPlugin;
use LaBoiteACode\DependencyGraph\DependencyGraphPlugin;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Martin6363\SidebarResize\SidebarResizePlugin;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use Syofyanzuhad\ConnectionIndicator\ConnectionIndicatorPlugin;
use UniFileManager\FilamentFileManager\FilamentFileManagerPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;
use Vaslv\FilamentAppVersion\Resolvers\ConfigVersionResolver;
use Vaslv\FilamentAppVersion\Resolvers\FileVersionResolver;
use Vaslv\FilamentAppVersion\Resolvers\GitVersionResolver;
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
            ->brandName('MAMIAS Web Application')
            ->brandLogo(asset('images/mamias.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/favicon-32x32.png'))
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
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification(EmailVerificationPrompt::class)
            ->plugins([
                // Version sources, highest priority first. See
                // config/filament-app-version.php — keep the two chains in step.
                //   1. APP_VERSION, frozen into the config at config:cache time
                //   2. a VERSION file written into the image by the build
                //   3. the short commit SHA — host-side dev only: .git sits at
                //      the repo root, one level above the Laravel app
                AppVersionPlugin::make()
                    ->resolvers([
                        ConfigVersionResolver::make('filament-app-version.version'),
                        FileVersionResolver::make(base_path('VERSION')),
                        GitVersionResolver::make(base_path('../.git')),
                    ])
                    ->fallback('dev')
                    ->prefix('v')
                    ->badge()
                    // ->neutral()
                    ->size(Size::Small)
                    ->verticalAlignment(VerticalAlignment::Center)
                    ->renderHooks([
                        PanelsRenderHook::TOPBAR_LOGO_AFTER,
                        PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                    ])
                    ->tooltip(fn (): string => __('Application version')),

                DependencyGraphPlugin::make()
                    ->visible(fn () => app()->environment('local') || auth()->user()?->hasRole('super_admin'))
                    ->navigationLabel('Architecture')
                    ->navigationIcon('heroicon-o-share')
                    ->activeNavigationIcon('heroicon-s-share')
                    ->navigationGroup('System')      // string, enum or closure
                    // ->navigationSort(30)
                    // ->navigationParentItem('Tooling')
                    ->navigationBadge(fn (): string => 'beta'),
                // ->registerNavigation(false)               // keep the route, hide the menu entry
                // ->slug('architecture-map')
                // ->cluster(\App\Filament\Clusters\Developer::class)
                // ->maxContentWidth(Width::SevenExtraLarge),
                FilamentNotificationCenterPlugin::make(),
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
                ConnectionIndicatorPlugin::make(),
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
                        'Admin' => config('services.dev_login.admin_email'),
                        'Scientist' => config('services.dev_login.scientist_email'),
                        'Public User' => config('services.dev_login.public_email'),
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
                    ->withBorder(),
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
                FilamentLogsExplorerPlugin::make()
                    ->navigationLabel('Application logs')
                    ->navigationIcon('heroicon-o-bug-ant')
                    ->activeNavigationIcon('heroicon-s-bug-ant')
                    ->navigationGroup('System')
                    ->channels(['daily', 'single'])
                    ->excludeChannels(['emergency'])
                    ->expandStacks()
                    ->filesPerChannel(20)
                    ->discoverUntrackedFiles(directory: storage_path('logs')),
                ActivityLogPlugin::make()
                    ->navigationGroup('System'),
                // Structured, searchable exception records with request and
                // stack context. FilamentLogsExplorerPlugin above only tails
                // the raw log files, so a thrown exception is prose there.
                FilamentExceptionsPlugin::make()
                    ->navigationGroup('System')
                    ->navigationLabel('Exceptions')
                    ->navigationIcon('heroicon-o-exclamation-triangle')
                    ->activeNavigationIcon('heroicon-s-exclamation-triangle'),
                // The Taxon/IntroEventRecord importers and the WoRMS and GBIF
                // fetches all run on the queue container. The progress widgets
                // only cover a run in flight; this is the durable record.
                FilamentJobsMonitorPlugin::make()
                    ->navigationGroup('System')
                    ->navigationIcon('heroicon-o-cpu-chip')
                    ->navigationCountBadge(),
                FilamentFileManagerPlugin::make()
                    ->page(FileManager::class)
                    ->navigationLabel('File Manager')
                    ->navigationIcon('heroicon-o-folder-open')
                    ->navigationGroup('System'),
            ])
            /* Même typographie que le site public — voir DESIGN-SYSTEM.md.
               Sans ces deux lignes le panneau garde l'Inter de Filament et
               diverge visuellement du site.

               Provider explicite des deux côtés : `font()` ne réécrit le
               provider que si l'argument est non-null, donc un précédent
               ->font(..., provider: GoogleFontProvider::class) restait collé
               et servait le sans depuis Google pendant que le mono venait de
               Bunny — deux CDN pour une seule typographie. Bunny est le miroir
               RGPD de Google Fonts, et c'est aussi ce que charge le site
               public (app.blade.php). */
            ->font('Geist', provider: BunnyFontProvider::class)
            ->monoFont('Geist Mono', provider: BunnyFontProvider::class)
            ->colors([
                /* Teal du logo, échantillonné sur public/images/Logoweb.png.
                   Deux nuances, deux rôles — voir DESIGN-SYSTEM.md :
                   500 ne porte jamais de texte (bordures, anneaux de focus,
                   icônes : 3:1 suffit) ; 600 est le fond des boutons pleins
                   sous du blanc et doit tenir 4.5:1 — il mesure 7.08:1. */
                'primary' => [
                    50 => '#eaf6f8',
                    100 => '#d3edf1',
                    200 => '#a9dbe3',
                    300 => '#6fc3d0',
                    400 => '#29a3b7',
                    500 => '#078da0', // ← Teal exact du logo
                    600 => '#056273', // ← Fond plein sous texte blanc, 7.08:1
                    700 => '#044e5c', // ← Survol actif
                    800 => '#033b46',
                    900 => '#022c35',
                    950 => '#011e24',
                ],
                /* Slate tiré vers le bleu du logo : les gris s'assoient à côté
                   du teal sans virer au boueux. C'est la seule source des
                   --gray-* (Filament les injecte au runtime), donc un
                   @theme dans theme.css ne suffirait pas. 200 est le filet
                   de 1px qui porte toute la structure ; 500 est le plancher
                   du texte discret, à 4.9:1 sur blanc. */
                'gray' => [
                    50 => '#f7fafb',
                    100 => '#edf3f5',
                    200 => '#d8e3e8', // ← Le filet
                    300 => '#bfd0d8',
                    400 => '#9fb4be',
                    500 => '#5f7783', // ← Texte discret, 4.9:1
                    600 => '#47606b',
                    700 => '#2b4652',
                    800 => '#1a333f',
                    900 => '#0e2630', // ← L'encre
                    950 => '#08191f',
                ],
                // 'danger' is registered in AppServiceProvider (the invasive ramp).
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
            )
            // Installable-app manifest and the Install / Full screen buttons,
            // shared with the public layout.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('partials.pwa-head'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('partials.app-controls'),
            )
            // Dropped by accident in 8566db5; the view gates itself to panel roles.
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('filament.hooks.pending-references-alert'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('filament.hooks.accepted-names-alert'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('filament.hooks.public-site-link'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn (): Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View => view('filament.hooks.public-site-link'),
            )
            // Vacuum probes the server while the navigation builds and refuses
            // to query inside an open transaction — which every RefreshDatabase
            // test is — so registering it under test 500s every panel page.
            ->plugins(app()->runningUnitTests() ? [] : [VacuumPlugin::make()]);
    }
}
